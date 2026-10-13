// sw.js - Service Worker for PWA
const CACHE_NAME = 'taskmanager-cache-v1';
const URLS_TO_CACHE = [
    '/assets/css/style.css',
    '/assets/css/premium.css',
    '/assets/css/variables.css',
    '/assets/css/buttons.css',
    '/assets/css/cards.css',
    '/assets/css/forms.css',
    '/assets/css/animations.css',
    '/assets/js/app.js'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(URLS_TO_CACHE))
    );
});

self.addEventListener('fetch', event => {
    // Only cache static assets (CSS, JS, Images)
    const isStaticAsset = event.request.url.match(/\.(css|js|png|jpg|jpeg|gif|svg)$/i);

    if (event.request.method !== 'GET' || !isStaticAsset) {
        return; // Let browser handle API calls and dynamic HTML normally
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response; // Return cached asset
                }
                return fetch(event.request).then(
                    function(response) {
                        if(!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        var responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                            });
                        return response;
                    }
                );
            })
    );
});
