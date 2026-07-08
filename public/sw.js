/* eslint-disable no-restricted-globals */
const CACHE_NAME = 'check-planilha-v2';
const STATIC_CACHE = 'check-planilha-static-v2';

const PRECACHE_URLS = [
  '/',
  '/login',
  '/reports',
  '/offline.html',
  '/manifest.json',
  '/favicon.ico',
  '/apple-touch-icon.png',
  '/icons/pwa-48x48.png',
  '/icons/pwa-72x72.png',
  '/icons/pwa-96x96.png',
  '/icons/pwa-128x128.png',
  '/icons/pwa-144x144.png',
  '/icons/pwa-152x152.png',
  '/icons/pwa-192x192.png',
  '/icons/pwa-192x192-maskable.png',
  '/icons/pwa-384x384.png',
  '/icons/pwa-512x512.png',
  '/icons/pwa-512x512-maskable.png',
];

function isStaticAsset(url) {
  const { pathname } = new URL(url);
  return (
    pathname.startsWith('/assets/') ||
    pathname.endsWith('.css') ||
    pathname.endsWith('.js') ||
    /\.(png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)$/.test(pathname) ||
    pathname === '/manifest.json' ||
    pathname === '/favicon.ico' ||
    pathname === '/apple-touch-icon.png'
  );
}

function isNavigation(request) {
  return request.mode === 'navigate' || (request.method === 'GET' && request.headers.get('Accept')?.includes('text/html'));
}

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) {
    return cached;
  }

  try {
    const response = await fetch(request);
    if (response && response.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, response.clone()).catch(() => {});
    }
    return response;
  } catch (error) {
    if (isNavigation(request)) {
      const root = await caches.match('/');
      if (root) {
        return root;
      }
    }

    return new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
  }
}

async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response && response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone()).catch(() => {});
    }
    return response;
  } catch (error) {
    const cached = await caches.match(request);
    if (cached) {
      return cached;
    }

    if (isNavigation(request)) {
      const root = await caches.match('/');
      if (root) {
        return root;
      }
    }

    return new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
  }
}

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(STATIC_CACHE);
    await cache.addAll(PRECACHE_URLS);
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter((key) => key !== CACHE_NAME && key !== STATIC_CACHE).map((key) => caches.delete(key)));
    await self.clients.claim();
  })());
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

  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/_debugbar') || url.pathname.startsWith('/livewire/')) {
    return;
  }

  if (isStaticAsset(url)) {
    event.respondWith(cacheFirst(request));
    return;
  }

  event.respondWith(networkFirst(request));
});
