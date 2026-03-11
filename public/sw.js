// Service Worker - Check Planilha PWA
const CACHE_VERSION = 'v4.0.0';
const CACHE_NAME = `checkplanilha-${CACHE_VERSION}`;

// Arquivos essenciais para cache (offline-first)
const STATIC_CACHE = [
  '/',
  '/manifest-prod.json',
  '/assets/images/logo.png',
  '/assets/css/app-layout.css',
  '/assets/css/celular-container.css',
  '/assets/css/header-mobile.css',
  '/assets/css/footer-mobile.css',
  '/assets/js/pwa-install.js',
  '/assets/js/layouts/app.js',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

// Recursos dinâmicos (network-first)

// Recursos dinâmicos (network-first)
const DYNAMIC_CACHE_PATTERNS = [
  /\/spreadsheets\//,
  /\/products\//,
  /\/menu/,
  /\/assets\//
];

// Instalação - cacheia arquivos estáticos
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(STATIC_CACHE).catch(() => {
          // Continuar mesmo com falhas parciais
        });
      })
      .then(() => {
        return self.skipWaiting(); // Ativar imediatamente
      })
  );
});

// Ativação - limpa caches antigos e assume controle
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME && cacheName.startsWith('checkplanilha-')) {
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        return self.clients.claim(); // Assumir controle imediatamente
      })
  );
});

// Fetch - estratégia híbrida (Network First para API, Cache First para assets)
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar requisições não-GET
  if (request.method !== 'GET') {
    return;
  }

  // Ignorar Chrome extensions e similar
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // Estratégia: Cache First para assets estáticos
  if (url.pathname.startsWith('/assets/') || url.hostname.includes('cdn.jsdelivr.net')) {
    event.respondWith(
      caches.match(request)
        .then(cachedResponse => {
          if (cachedResponse) {
            // Retornar cache imediatamente
            return cachedResponse;
          }
          
          // Buscar na rede e cachear
          return fetch(request).then(networkResponse => {
            if (networkResponse && networkResponse.status === 200) {
              const responseToCache = networkResponse.clone();
              caches.open(CACHE_NAME).then(cache => {
                cache.put(request, responseToCache);
              });
            }
            return networkResponse;
          });
        })
        .catch(() => {
          // Fallback genérico para assets — silencioso em produção
        })
    );
    return;
  }

  // Estratégia: Network First para páginas e API
  event.respondWith(
    fetch(request)
      .then(networkResponse => {
        // Cachear páginas dinâmicas bem-sucedidas
        if (networkResponse && networkResponse.status === 200 && 
            DYNAMIC_CACHE_PATTERNS.some(pattern => pattern.test(url.pathname))) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        // Fallback para cache em caso de offline
        return caches.match(request).then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          // Página offline genérica
          if (request.headers.get('accept').includes('text/html')) {
            return new Response(
              `<!DOCTYPE html>
              <html lang="pt-br">
              <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Offline - Check Planilha</title>
                <style>
                  body {
                    font-family: system-ui, -apple-system, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-align: center;
                    padding: 20px;
                  }
                  .offline-container {
                    max-width: 400px;
                  }
                  h1 { font-size: 3rem; margin: 0; }
                  p { font-size: 1.2rem; opacity: 0.9; }
                  button {
                    margin-top: 20px;
                    padding: 12px 24px;
                    font-size: 1rem;
                    background: white;
                    color: #667eea;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: bold;
                  }
                  button:hover { transform: scale(1.05); }
                </style>
              </head>
              <body>
                <div class="offline-container">
                  <h1>📵</h1>
                  <h2>Você está offline</h2>
                  <p>Verifique sua conexão com a internet e tente novamente.</p>
                  <button onclick="window.location.reload()">Tentar Novamente</button>
                </div>
              </body>
              </html>`,
              {
                status: 503,
                statusText: 'Service Unavailable',
                headers: { 'Content-Type': 'text/html; charset=utf-8' }
              }
            );
          }
        });
      })
  );
});
