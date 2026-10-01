const CACHE_PREFIX = 'ceklaundry-static-';
const BUILD = new URL(self.location.href).searchParams.get('v') || 'initial';
const CACHE_NAME = CACHE_PREFIX + BUILD;
const OFFLINE_PAGE = '/offline.html';

function isHashedAsset(url) {
  return url.origin === self.location.origin && !url.search &&
    /^\/build\/assets\/.+-[A-Za-z0-9_-]{8,}\.(?:js|css|svg|png|webp|woff2?)$/.test(url.pathname);
}

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_PAGE)));
  if (!self.registration.active) self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(Promise.all([
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key.startsWith(CACHE_PREFIX) && key !== CACHE_NAME).map((key) => caches.delete(key)))),
    self.clients.claim(),
  ]));
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'ACTIVATE') self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_PAGE)));
    return;
  }
  if (!isHashedAsset(url)) return;
  event.respondWith(caches.open(CACHE_NAME).then(async (cache) => {
    const cached = await cache.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (response.ok && response.type === 'basic') await cache.put(request, response.clone());
    return response;
  }));
});
