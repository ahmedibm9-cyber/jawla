const CACHE = "jawla-shell-v5";
const SHELL = ["/", "/app", "/manifest.json", "/offline"];

// Install: cache shell assets
self.addEventListener("install", (e) => {
  e.waitUntil(
    caches
      .open(CACHE)
      .then((c) => c.addAll(SHELL))
      .then(() => self.skipWaiting())
  );
});

// Activate: clean old caches + claim clients
self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))
        )
      )
      .then(() => self.clients.claim())
  );
});

// Fetch: strategy per request type
self.addEventListener("fetch", (e) => {
  if (e.request.method !== "GET") return;

  // Navigation: cache-first for cached pages, network-first for others
  if (e.request.mode === "navigate") {
    e.respondWith(
      caches.match(e.request).then((cached) => {
        if (cached) return cached;
        return fetch(e.request)
          .then((response) => {
            const clone = response.clone();
            caches.open(CACHE).then((c) => c.put(e.request, clone));
            return response;
          })
          .catch(() => caches.match("/offline"));
      })
    );
    return;
  }

  // Static assets: cache-first
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

  // API / other: network-first
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
