const VERSION = 'eh360-v1-20260929';
const SHELL_CACHE = `${VERSION}-shell`;
const RUNTIME_CACHE = `${VERSION}-runtime`;
const DOWNLOAD_CACHE = `${VERSION}-downloads`;

const SHELL = [
    '/offline',
    '/manifest.webmanifest',
    '/pwa.js',
    '/icons/pwa-192.png',
    '/icons/pwa-512.png',
    '/icons/pwa-maskable-192.png',
    '/icons/pwa-maskable-512.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(SHELL_CACHE)
            .then(cache => cache.addAll(SHELL))
    );
});

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const keep = new Set([SHELL_CACHE, RUNTIME_CACHE, DOWNLOAD_CACHE]);
        const names = await caches.keys();

        await Promise.all(
            names
                .filter(name => name.startsWith('eh360-') && !keep.has(name))
                .map(name => caches.delete(name))
        );

        await self.clients.claim();
    })());
});

self.addEventListener('message', event => {
    const data = event.data || {};

    if (data.type === 'SKIP_WAITING') {
        self.skipWaiting();
        return;
    }

    if (data.type === 'CACHE_URLS' && Array.isArray(data.urls)) {
        event.waitUntil((async () => {
            const cache = await caches.open(DOWNLOAD_CACHE);

            for (const rawUrl of data.urls) {
                try {
                    const url = new URL(rawUrl, self.location.origin);
                    if (url.origin !== self.location.origin) continue;

                    const response = await fetch(url.href, {
                        credentials: 'include',
                        cache: 'no-store'
                    });

                    if (response.ok) {
                        await cache.put(url.href, response.clone());
                    }
                } catch (_) {
                    // Individual download failures should not abort the batch.
                }
            }

            const client = event.source;
            client?.postMessage({type: 'EH360_DOWNLOADS_UPDATED'});
        })());
    }

    if (data.type === 'REMOVE_CACHED_URLS' && Array.isArray(data.urls)) {
        event.waitUntil((async () => {
            const cache = await caches.open(DOWNLOAD_CACHE);
            for (const rawUrl of data.urls) {
                const url = new URL(rawUrl, self.location.origin);
                await cache.delete(url.href);
            }
        })());
    }
});

self.addEventListener('sync', event => {
    if (event.tag === 'eh360-sync') {
        event.waitUntil(
            self.clients.matchAll({type: 'window', includeUncontrolled: true})
                .then(clients => clients.forEach(client => {
                    client.postMessage({type: 'EH360_SYNC_REQUESTED'});
                }))
        );
    }
});

self.addEventListener('fetch', event => {
    const request = event.request;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) return;

    if (url.pathname.startsWith('/admin/') || url.pathname.startsWith('/instructor/')) {
        return;
    }

    if (url.pathname.startsWith('/api/v1/participant/')) {
        event.respondWith(networkFirst(request, RUNTIME_CACHE));
        return;
    }

    if (
        request.destination === 'style'
        || request.destination === 'script'
        || request.destination === 'font'
        || request.destination === 'image'
        || url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
    ) {
        event.respondWith(cacheFirst(request, RUNTIME_CACHE));
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(navigationResponse(request));
    }
});

async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            await cache.put(request, response.clone());
        }
        return response;
    } catch (_) {
        return caches.match('/offline');
    }
}

async function networkFirst(request, cacheName) {
    try {
        const response = await fetch(request, {credentials: 'include'});
        if (response.ok) {
            const cache = await caches.open(cacheName);
            await cache.put(request, response.clone());
        }
        return response;
    } catch (_) {
        const cached = await caches.match(request);
        if (cached) return cached;
        return new Response(
            JSON.stringify({offline: true, message: 'Cached data is not available for this request.'}),
            {status: 503, headers: {'Content-Type': 'application/json'}}
        );
    }
}

async function navigationResponse(request) {
    const downloaded = await caches.open(DOWNLOAD_CACHE);
    const explicit = await downloaded.match(request);

    if (explicit) {
        try {
            const fresh = await fetch(request, {credentials: 'include'});
            if (fresh.ok) {
                await downloaded.put(request, fresh.clone());
                return fresh;
            }
        } catch (_) {
            return explicit;
        }
    }

    try {
        return await fetch(request, {credentials: 'include'});
    } catch (_) {
        return explicit || caches.match('/offline');
    }
}


self.addEventListener('push', event => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (_) {
        payload = {
            title: 'ElevateHer360',
            body: event.data ? event.data.text() : 'You have a new update.'
        };
    }

    const title = payload.title || 'ElevateHer360';
    const options = {
        body: payload.body || payload.message || 'You have a new update.',
        icon: '/icons/pwa-192.png',
        badge: '/icons/pwa-192.png',
        data: {
            url: payload.url || payload.link || '/'
        }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const target = event.notification.data?.url || '/';

    event.waitUntil((async () => {
        const clients = await self.clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        });

        for (const client of clients) {
            if ('focus' in client) {
                await client.focus();
                client.postMessage({type: 'EH360_NAVIGATE', url: target});
                return;
            }
        }

        if (self.clients.openWindow) {
            await self.clients.openWindow(target);
        }
    })());
});
