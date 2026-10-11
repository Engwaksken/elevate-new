const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {test} = require('node:test');
const vm = require('node:vm');

const source = readFileSync(`${__dirname}/../../public/sw.js`, 'utf8');

function worker(fetch, timers = {}) {
    const handlers = {};
    const notifications = [];
    const cached = [];
    const context = {
        URL, Response, AbortController, setTimeout, clearTimeout, ...timers, fetch,
        caches: {
            open: async () => ({
                addAll: async urls => cached.push(...urls),
                put: async () => {}
            })
        },
        self: {
            location: {origin: 'https://example.test'},
            addEventListener: (name, handler) => { handlers[name] = handler; },
            registration: {
                showNotification: async (title, options) => notifications.push({title, ...options})
            }
        }
    };
    vm.runInNewContext(source, context);
    return {handlers, notifications, cached};
}

async function push(instance) {
    let done;
    instance.handlers.push({waitUntil: promise => { done = promise; }});
    await done;
    return instance.notifications[0];
}

test('install shell does not depend on the dynamic manifest', async () => {
    const instance = worker(() => { throw new Error('Unavailable'); });
    let done;
    instance.handlers.install({waitUntil: promise => { done = promise; }});
    await done;
    assert(!instance.cached.includes('/app-manifest.webmanifest'));
    assert(instance.cached.includes('/icons/pwa-192.png'));
});

test('push uses trusted uploaded logo for icon and badge, skipping unsafe candidates', async () => {
    const instance = worker(async () => ({ok: true, json: async () => ({icons: [
        {src: 'https://other.test/brand-assets/logo'},
        {src: '/brand-assets/logo-spoof'},
        {src: 123}, null,
        {src: '/brand-assets/logo?v=content'}
    ]})}));
    const notification = await push(instance);
    assert.equal(notification.icon, 'https://example.test/brand-assets/logo?v=content');
    assert.equal(notification.badge, notification.icon);
});

test('push retains bundled fallbacks for missing, invalid, and unavailable manifests', async () => {
    for (const fetch of [
        async () => { throw new Error('Offline'); },
        async () => ({ok: false}),
        async () => ({ok: true, json: async () => { throw new Error('Invalid JSON'); }}),
        async () => ({ok: true, json: async () => ({icons: {src: '/brand-assets/logo'}})}),
        async () => ({ok: true, json: async () => ({icons: [{src: 'https://other.test/brand-assets/logo'}]})})
    ]) {
        const notification = await push(worker(fetch));
        assert.equal(notification.icon, '/icons/pwa-192.png');
        assert.equal(notification.badge, '/icons/pwa-192.png');
    }
});

for (const stalledBody of [false, true]) {
test(`a stalled manifest ${stalledBody ? 'body' : 'fetch'} does not block push fallback`, async () => {
    let signal;
    const instance = worker((url, options) => {
        signal = options.signal;
        return stalledBody
            ? Promise.resolve({ok: true, json: () => new Promise(() => {})})
            : new Promise(() => {});
    }, {
        setTimeout: callback => { queueMicrotask(callback); return 1; },
        clearTimeout: () => {}
    });
    const notification = await push(instance);
    assert.equal(signal.aborted, true);
    assert.equal(notification.icon, '/icons/pwa-192.png');
    assert.equal(notification.badge, notification.icon);
});
}

test('manifest and branding assets refresh from network at runtime', async () => {
    const requests = [];
    const instance = worker(async (request, options) => {
        requests.push(request.url);
        if (request.url.includes('/brand-assets/')) assert.equal(options.cache, 'no-store');
        return new Response('fresh');
    });
    for (const path of ['/app-manifest.webmanifest', '/brand-assets/logo?v=same', '/brand-assets/favicon']) {
        let response;
        instance.handlers.fetch({
            request: {method: 'GET', url: `https://example.test${path}`, destination: 'image'},
            respondWith: promise => { response = promise; }
        });
        assert.equal(await (await response).text(), 'fresh');
    }
    assert.equal(requests.length, 3);
});
