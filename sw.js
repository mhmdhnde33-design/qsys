// نسخة محسّنة من service worker
const CACHE_NAME = "qsys-static-v2";
const RUNTIME_CACHE = "qsys-runtime-v1";
const STATIC_ASSETS = [
  "/index.php",
  "/display.php",
  "/js/main.js",
  "/js/display.js",
  "/assets/logo.png.webp",
  "/manifest.json",
  "/offline.html"
];

// تثبيت وادخال الملفات الثابتة
self.addEventListener("install", (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
  );
});

// تفعيل وتنظيف الكاشات القديمة
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.map(key => {
        if (key !== CACHE_NAME && key !== RUNTIME_CACHE) {
          return caches.delete(key);
        }
      })
    )).then(() => self.clients.claim())
  );
});

// سياسة الجلب: API => network-first, static => cache-first, fallback => offline.html
self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);

  // تجاوز صفحة الدخول/الموظف (لا نكاشن صفحات auth الحسّاسة)
  if (url.pathname.includes("employee_login.php") || url.pathname.includes("employee.php")) {
    return; // دع المتصفح يتعامل معها
  }

  // مباشَرة لطلبات API (يمكن تعديل المسارات لتتطابق مع API) — network-first
  if (url.pathname.startsWith("/api") || url.pathname.includes("/api/") || event.request.method !== "GET") {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // خلي نسخة في الكاش runtime لتحسين تجربة أثناء التبديل بين الشبكات
          return caches.open(RUNTIME_CACHE).then(cache => {
            cache.put(event.request, response.clone());
            return response;
          });
        })
        .catch(() => caches.match(event.request).then(r => r || caches.match("/offline.html")))
    );
    return;
  }

  // الملفات الثابتة — cache-first
  event.respondWith(
    caches.match(event.request).then(cachedResp => {
      if (cachedResp) return cachedResp;
      return fetch(event.request)
        .then(response => {
          // خزّن في runtime cache نسخة من الموارد الديناميكية
          return caches.open(RUNTIME_CACHE).then(cache => {
            cache.put(event.request, response.clone());
            return response;
          });
        })
        .catch(() => {
          // fallback لصفحة الأوفلاين عند فشل التحميل
          if (event.request.mode === "navigate") {
            return caches.match("/offline.html");
          }
        });
    })
  );
});
