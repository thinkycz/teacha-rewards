/*!
 * Teacha Rewards service worker.
 *
 * Strategy: precache the offline shell on install, network-first for HTML
 * navigations (so customers always see the latest wallet data when online),
 * and fall back to the cached /offline page when a navigation fails.
 *
 * Static assets use a stale-while-revalidate cache so the brand and shell
 * load instantly on repeat visits.
 */
const CACHE_VERSION = 'teacha-v1';
const OFFLINE_URL = '/offline';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/',
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(CACHE_VERSION);
            // Use { cache: 'reload' } to bypass the HTTP cache for the precache.
            await Promise.all(
                PRECACHE_URLS.map(async (url) => {
                    try {
                        const response = await fetch(url, { cache: 'reload' });
                        if (response.ok) {
                            await cache.put(url, response);
                        }
                    } catch (err) {
                        // Ignore — offline install, we'll fall back to /offline on the next request.
                    }
                }),
            );
            await self.skipWaiting();
        })(),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(
                keys
                    .filter((key) => key !== CACHE_VERSION)
                    .map((key) => caches.delete(key)),
            );
            await self.clients.claim();
        })(),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    // 1) Navigations: network-first, fall back to cached /offline.
    if (request.mode === 'navigate') {
        event.respondWith(
            (async () => {
                try {
                    const fresh = await fetch(request);
                    const cache = await caches.open(CACHE_VERSION);
                    cache.put(request, fresh.clone());
                    return fresh;
                } catch (err) {
                    const cached = await caches.match(request);
                    return (
                        cached ??
                        caches.match(OFFLINE_URL) ??
                        new Response('Offline', {
                            status: 503,
                            statusText: 'Offline',
                        })
                    );
                }
            })(),
        );
        return;
    }

    // 2) Static assets: stale-while-revalidate.
    if (
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'image' ||
        request.destination === 'font' ||
        request.destination === 'manifest'
    ) {
        event.respondWith(
            (async () => {
                const cache = await caches.open(CACHE_VERSION);
                const cached = await cache.match(request);
                const network = fetch(request)
                    .then((response) => {
                        if (response.ok) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    })
                    .catch(() => cached);
                return cached ?? network;
            })(),
        );
    }
});
