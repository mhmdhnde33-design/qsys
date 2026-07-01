const CACHE_NAME = "queue-display-v1";
const STATIC_ASSETS = [
  "./display.php",
  "./index.php",
  "./employee.php",
  "./employee_login.php",
  "./js/display.js",
  "./js/main.js",
  "./js/employee.js",
  "./assets/logo.png.webp",
  "./manifest.json",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    }),
  );
});

self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);

  // If the page is an auth page, bypass the Service Worker cache entirely
  if (
    url.pathname.includes("employee_login.php") ||
    url.pathname.includes("employee.php")
  ) {
    return; // This allows the browser to handle the redirect normally
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      return cachedResponse || fetch(event.request);
    }),
  );
});
