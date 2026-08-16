/* eslint-disable no-restricted-globals */
const CACHE_NAME = 'check-planilha-pwa-v1';
const PRECACHE_URLS = [
  '/',
  '/login',
  '/offline.html',
  '/manifest.json',
  '/favicon.ico',
  '/icons/pwa-180.png',
  '/icons/pwa-192.png',
  '/icons/pwa-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE_NAME);
    await cache.addAll(PRECACHE_URLS);
    self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)));
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        return await fetch(request);
      } catch (error) {
        const cache = await caches.open(CACHE_NAME);
        const offline = await cache.match('/offline.html');
        if (offline) {
          return offline;
        }

        return new Response('Sem conexão', {
          status: 503,
          headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
      }
    })());
    return;
  }

  if (!['style', 'script', 'image', 'font'].includes(request.destination)) {
    return;
  }

  event.respondWith((async () => {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);

    if (cached) {
      event.waitUntil((async () => {
        try {
          const fresh = await fetch(request);
          if (fresh && fresh.ok) {
            await cache.put(request, fresh.clone());
          }
        } catch (error) {
          // Mantém a versão em cache quando a atualização falha.
        }
      })());

      return cached;
    }

    try {
      const response = await fetch(request);
      if (response && response.ok) {
        cache.put(request, response.clone()).catch(() => {});
      }
      return response;
    } catch (error) {
      if (request.destination === 'image') {
        return new Response('', { status: 204 });
      }

      return new Response('', { status: 503, headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
    }
  })());
});
