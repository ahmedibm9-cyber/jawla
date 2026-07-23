const CACHE = "jawla-public-v6";
const PUBLIC_SHELL = ["/offline", "/manifest.json"];

function isCacheablePublicAsset(request) {
  const url = new URL(request.url);

  return (
    url.origin === self.location.origin &&
    request.method === "GET" &&
    (url.pathname.startsWith("/build/") ||
      url.pathname.startsWith("/images/") ||
      url.pathname.startsWith("/icons/") ||
      ["/manifest.json", "/favicon.ico"].includes(url.pathname))
  );
}

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PUBLIC_SHELL))
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((key) => key.startsWith("jawla-") && key !== CACHE)
            .map((key) => caches.delete(key))
        )
      )
  );
});

self.addEventListener("message", (event) => {
  if (event.data?.type === "PURGE_USER_DATA") {
    event.waitUntil(
      caches
        .keys()
        .then((keys) =>
          Promise.all(
            keys
              .filter((key) => key.startsWith("jawla-"))
              .map((key) => caches.delete(key))
          )
        )
    );
  }

  // A waiting worker is activated only after an explicit client decision.
  if (event.data?.type === "ACTIVATE_UPDATE") {
    self.skipWaiting();
  }
});

self.addEventListener("fetch", (event) => {
  if (event.request.method !== "GET") return;

  // Authenticated HTML, API, PDFs, and all other dynamic responses always use
  // the network. They are never placed in Cache Storage or served after logout.
  if (event.request.mode === "navigate") {
    event.respondWith(
      fetch(event.request).catch(async () => {
        const cachedOffline = await caches.match("/offline");
        if (cachedOffline) {
          return cachedOffline;
        }
        // If even the offline page is not cached, return a basic offline response.
        return new Response(
          "<h1>Offline</h1><p>You are offline and the offline page is not available.</p>",
          { headers: { "Content-Type": "text/html" } }
        );
      })
    );
    return;
  }

  if (!isCacheablePublicAsset(event.request)) return;

  event.respondWith(
    caches.match(event.request).then(
      (cached) =>
        cached ||
        fetch(event.request).then((response) => {
          if (!response.ok || response.type !== "basic") return response;
          const copy = response.clone();
          event.waitUntil(
            caches.open(CACHE).then((cache) => cache.put(event.request, copy))
          );
          return response;
        })
    )
  );
});
