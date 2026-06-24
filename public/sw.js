/* Service worker Chaudoudoux — PWA installable.
 * Stratégie :
 *  - assets statiques (/assets/...) → cache-first (rapide, hors-ligne partiel)
 *  - navigation (pages HTML)        → network-first (jamais de page périmée)
 *  - on ne touche JAMAIS aux requêtes non-GET (déclarations POST, signature…)
 */
const CACHE = 'chaudoudoux-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Ne rien intercepter d'autre que les GET de même origine.
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Assets statiques → cache-first
    if (url.pathname.startsWith('/assets/')) {
        event.respondWith((async () => {
            const cache = await caches.open(CACHE);
            const hit = await cache.match(req);
            if (hit) return hit;
            try {
                const res = await fetch(req);
                if (res && res.ok) cache.put(req, res.clone());
                return res;
            } catch (e) {
                return hit || Response.error();
            }
        })());
        return;
    }

    // Pages (navigation) → network-first, repli cache si hors-ligne
    if (req.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                return await fetch(req);
            } catch (e) {
                const cache = await caches.open(CACHE);
                return (await cache.match(req)) || (await cache.match('/')) || Response.error();
            }
        })());
        return;
    }
});
