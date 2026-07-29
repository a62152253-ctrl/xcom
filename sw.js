const CACHE_NAME = "taskmanager-cache-v1";
const urlsToCache = [
  "/",
  "/pages/dashboard.php",
  "/assets/css/style.css",
  "/assets/js/app.js",
  "/assets/css/variables.css",
  "/assets/css/buttons.css",
  "/assets/css/cards.css",
  "/assets/css/forms.css",
  "/assets/css/animations.css"
];

self.addEventListener("install", event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener("fetch", event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      })
  );
});
