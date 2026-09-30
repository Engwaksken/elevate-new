(() => {
    'use strict';

    const DB_NAME = 'elevateher360-pwa';
    const DB_VERSION = 1;
    const OPERATIONS = 'operations';
    const META = 'meta';
    const DOWNLOADS = 'downloads';

    let deferredInstallPrompt = null;
    let registration = null;
    let syncing = false;

    const $ = selector => document.querySelector(selector);
    const $$ = selector => [...document.querySelectorAll(selector)];

    const statusEl = () => $('[data-pwa-status]');
    const lastSyncEl = () => $('[data-pwa-last-sync]');
    const installButtons = () => $$('[data-pwa-install]');
    const updateBox = () => $('[data-pwa-update]');
    const updateButton = () => $('[data-pwa-update-now]');

    function setStatus(label, state = '') {
        const node = statusEl();
        if (!node) return;
        node.textContent = label;
        node.dataset.state = state;
    }

    async function openDb() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = event => {
                const db = event.target.result;

                if (!db.objectStoreNames.contains(OPERATIONS)) {
                    const store = db.createObjectStore(OPERATIONS, {
                        keyPath: 'client_operation_id'
                    });
                    store.createIndex('created_at', 'created_at');
                }

                if (!db.objectStoreNames.contains(META)) {
                    db.createObjectStore(META, {keyPath: 'key'});
                }

                if (!db.objectStoreNames.contains(DOWNLOADS)) {
                    db.createObjectStore(DOWNLOADS, {keyPath: 'key'});
                }
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async function storePut(storeName, value) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readwrite');
            tx.objectStore(storeName).put(value);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    async function storeGet(storeName, key) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).get(key);
            req.onsuccess = () => resolve(req.result || null);
            req.onerror = () => reject(req.error);
        });
    }

    async function storeAll(storeName) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    }

    async function storeDelete(storeName, key) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(storeName, 'readwrite');
            tx.objectStore(storeName).delete(key);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    }

    async function loadLastSync() {
        const row = await storeGet(META, 'last_synced_at');
        const node = lastSyncEl();

        if (!node) return;

        if (!row?.value) {
            node.textContent = 'Not synced yet';
            return;
        }

        node.textContent = `Last synced ${new Date(row.value).toLocaleString()}`;
    }

    function onlineState() {
        if (navigator.onLine) {
            setStatus('Online', 'online');
        } else {
            setStatus('Offline', 'offline');
        }
    }

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    }

    function updateInstallVisibility() {
        const visible = !!deferredInstallPrompt && !isStandalone();
        installButtons().forEach(button => {
            button.hidden = !visible;
        });
    }

    async function installApp() {
        if (!deferredInstallPrompt) return;

        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        updateInstallVisibility();
    }

    async function queueAction(type, payload = {}) {
        const operation = {
            client_operation_id: crypto.randomUUID
                ? crypto.randomUUID()
                : `${Date.now()}-${Math.random().toString(16).slice(2)}`,
            type,
            payload,
            created_at: new Date().toISOString()
        };

        await storePut(OPERATIONS, operation);

        if (navigator.onLine) {
            syncPending();
        } else {
            setStatus('Offline — changes queued', 'offline');
        }

        return operation.client_operation_id;
    }

    async function syncPending() {
        if (syncing || !navigator.onLine) return;

        const operations = await storeAll(OPERATIONS);
        if (!operations.length) {
            await syncLatest();
            return;
        }

        syncing = true;
        setStatus('Synchronising…', 'syncing');

        try {
            const response = await fetch('/api/v1/participant/offline-actions', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({operations})
            });

            if (!response.ok) {
                throw new Error(`Sync failed with ${response.status}`);
            }

            const body = await response.json();

            for (const result of body.results || []) {
                if (result.status === 'processed' || result.status === 'duplicate') {
                    await storeDelete(OPERATIONS, result.client_operation_id);
                }
            }

            await syncLatest();
            setStatus('Online', 'online');
        } catch (error) {
            console.warn('ElevateHer360 sync failed', error);
            setStatus('Sync failed — Retry', 'error');
        } finally {
            syncing = false;
        }
    }

    async function syncLatest() {
        try {
            const previous = await storeGet(META, 'last_synced_at');
            const params = new URLSearchParams();

            if (previous?.value) {
                params.set('last_synced_at', previous.value);
            }

            const endpoint = `/api/v1/participant/sync${params.toString() ? `?${params}` : ''}`;
            const response = await fetch(endpoint, {
                credentials: 'same-origin',
                headers: {'Accept': 'application/json'}
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const syncedAt = payload.last_synced_at || payload.server_time || new Date().toISOString();

            await storePut(META, {
                key: 'last_synced_at',
                value: syncedAt
            });

            await loadLastSync();

            window.dispatchEvent(new CustomEvent('eh360:pwa-synced', {
                detail: payload
            }));
        } catch (_) {
            // Non-participant pages may legitimately have no API session.
        }
    }

    async function downloadForOffline(button) {
        const urls = (button.dataset.offlineDownload || '')
            .split(',')
            .map(value => value.trim())
            .filter(Boolean);

        if (!urls.length || !navigator.serviceWorker.controller) return;

        button.disabled = true;
        const previous = button.textContent;
        button.textContent = 'Downloading…';

        navigator.serviceWorker.controller.postMessage({
            type: 'CACHE_URLS',
            urls
        });

        const key = button.dataset.offlineKey || urls[0];

        await storePut(DOWNLOADS, {
            key,
            urls,
            updated_at: new Date().toISOString()
        });

        button.textContent = 'Available offline';

        setTimeout(() => {
            button.disabled = false;
            button.textContent = previous;
        }, 2200);
    }

    async function removeOfflineDownload(button) {
        const key = button.dataset.offlineRemove;
        if (!key) return;

        const item = await storeGet(DOWNLOADS, key);
        if (!item) return;

        navigator.serviceWorker.controller?.postMessage({
            type: 'REMOVE_CACHED_URLS',
            urls: item.urls || []
        });

        await storeDelete(DOWNLOADS, key);
        window.dispatchEvent(new CustomEvent('eh360:pwa-download-removed', {
            detail: {key}
        }));
    }

    function showUpdate(reg) {
        if (!reg.waiting) return;

        const box = updateBox();
        if (box) box.hidden = false;

        const button = updateButton();
        button?.addEventListener('click', () => {
            reg.waiting?.postMessage({type: 'SKIP_WAITING'});
        }, {once: true});
    }

    async function registerServiceWorker() {
        if (!('serviceWorker' in navigator)) return;

        registration = await navigator.serviceWorker.register('/sw.js', {
            scope: '/'
        });

        if (registration.waiting) {
            showUpdate(registration);
        }

        registration.addEventListener('updatefound', () => {
            const worker = registration.installing;
            if (!worker) return;

            worker.addEventListener('statechange', () => {
                if (
                    worker.state === 'installed'
                    && navigator.serviceWorker.controller
                ) {
                    showUpdate(registration);
                }
            });
        });

        let reloading = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (reloading) return;
            reloading = true;
            window.location.reload();
        });

        navigator.serviceWorker.addEventListener('message', event => {
            if (event.data?.type === 'EH360_SYNC_REQUESTED') {
                syncPending();
            }
        });
    }

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        deferredInstallPrompt = event;
        updateInstallVisibility();
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        updateInstallVisibility();
    });

    window.addEventListener('online', () => {
        onlineState();
        syncPending();
    });

    window.addEventListener('offline', onlineState);

    document.addEventListener('click', event => {
        const install = event.target.closest('[data-pwa-install]');
        if (install) {
            event.preventDefault();
            installApp();
            return;
        }

        const retry = event.target.closest('[data-pwa-sync-retry]');
        if (retry) {
            event.preventDefault();
            syncPending();
            return;
        }

        const download = event.target.closest('[data-offline-download]');
        if (download) {
            event.preventDefault();
            downloadForOffline(download);
            return;
        }

        const remove = event.target.closest('[data-offline-remove]');
        if (remove) {
            event.preventDefault();
            removeOfflineDownload(remove);
        }
    });

    window.EH360PWA = {
        queueAction,
        syncPending,
        syncLatest,
        downloadForOffline,
        removeOfflineDownload,
        getDownloads: () => storeAll(DOWNLOADS),
        getPendingOperations: () => storeAll(OPERATIONS)
    };

    document.addEventListener('DOMContentLoaded', async () => {
        onlineState();
        updateInstallVisibility();
        await loadLastSync();

        try {
            await registerServiceWorker();
        } catch (error) {
            console.warn('ElevateHer360 service worker registration failed', error);
        }

        if (navigator.onLine) {
            syncPending();
        }
    });
})();
