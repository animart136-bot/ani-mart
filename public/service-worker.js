const CACHE_NAME = 'ani-mart-shell-v2';
const SHELL = [
  '/ani-mart/public/offline.html',
  '/ani-mart/public/assets/css/app.css',
  '/ani-mart/public/assets/css/components/navbar.css',
  '/ani-mart/public/assets/css/components/skeleton.css',
  '/ani-mart/public/assets/js/app.js',
  '/ani-mart/public/assets/js/skeleton.js',
  '/ani-mart/public/assets/img/ani-mart-logo.png',
  '/ani-mart/public/assets/img/favicon-32.png',
  '/ani-mart/public/assets/img/apple-touch-icon.png',
  '/ani-mart/public/assets/img/ani-mart-icon-192.png',
  '/ani-mart/public/assets/img/ani-mart-icon-512.png',
  '/ani-mart/public/assets/img/product-placeholder.svg'
];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(SHELL)));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match('/ani-mart/public/offline.html')));
    return;
  }

  if (url.pathname.includes('/assets/')) {
    event.respondWith(
      caches.match(request).then(cached => cached || fetch(request).then(response => {
        if (response.ok) {
          const copy = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
        }
        return response;
      }))
    );
  }
});
