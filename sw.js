const CACHE_NAME = 'taskpro-v1';
const ASSETS = [
    '/assets/css/style.css',
    '/assets/css/premium.css',
    '/assets/css/variables.css',
    '/assets/css/buttons.css',
    '/assets/css/cards.css',
    '/assets/css/forms.css',
    '/assets/css/animations.css',
    '/assets/js/app.js'
];

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE_NAME).then(c => c.addAll(ASSETS)));
});

self.addEventListener('fetch', (e) => {
    e.respondWith(caches.match(e.request).then(r => r || fetch(e.request)));
});
