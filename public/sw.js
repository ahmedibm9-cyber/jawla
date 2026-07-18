const CACHE = "jawla-shell-v3";
const SHELL = ["/", "/app", "/manifest.json", "/offline"];

self.addEventListener("install", (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)));
  self.skipWaiting();
});

self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))
        )
      )
  );
});

self.addEventListener("fetch", (e) => {
  if (e.request.method !== "GET") return;
  if (e.request.mode === "navigate") {
    e.respondWith(fetch(e.request).catch(() => caches.match("/offline")));
    return;
  }
  // Cache-first for static assets
  if (
    e.request.destination === "style" ||
    e.request.destination === "script" ||
    e.request.destination === "font" ||
    e.request.destination === "image"
  ) {
    e.respondWith(
      caches.match(e.request).then(
        (r) =>
          r ||
          fetch(e.request).then((response) => {
            const clone = response.clone();
            caches.open(CACHE).then((c) => c.put(e.request, clone));
            return response;
          })
      )
    );
    return;
  }
  // Network-first for other requests
  e.respondWith(
    fetch(e.request)
      .then((r) => {
        const clone = r.clone();
        caches.open(CACHE).then((c) => c.put(e.request, clone));
        return r;
      })
      .catch(() => caches.match(e.request))
  );
});
