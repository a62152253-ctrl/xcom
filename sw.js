self.addEventListener('install', event => {
  event.waitUntil(
    caches.open('taskmgr-v1').then(cache => {
      return cache.addAll([
        '/',
        '/assets/css/style.css',
        '/assets/css/premium.css',
        '/assets/js/app.js'
      ]);
    })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    })
  );
});