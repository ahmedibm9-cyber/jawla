const CACHE = "jawla-public-v7";
const SNAPSHOT_CACHE = "jawla-snapshot-v1";
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
            .filter(
              (key) =>
                (key.startsWith("jawla-") && key !== CACHE && key !== SNAPSHOT_CACHE)
            )
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

// Background Sync — when connectivity returns, notify all clients to flush.
self.addEventListener("sync", (event) => {
  if (event.tag === "jawla-sync") {
    event.waitUntil(
      self.clients.matchAll().then((clients) => {
        for (const client of clients) {
          client.postMessage({ type: "BACKGROUND_SYNC" });
        }
      })
    );
  }
});

// Periodic Background Sync — refresh offline snapshot data periodically
// when the app is installed and the browser supports it.
self.addEventListener("periodicsync", (event) => {
  if (event.tag === "jawla-periodic-sync") {
    event.waitUntil(
      self.clients.matchAll({ type: "window" }).then((clients) => {
        for (const client of clients) {
          if (client.url.includes("/app")) {
            client.postMessage({ type: "PERIODIC_SYNC" });
          }
        }
      })
    );
  }
});

// Push Notifications
self.addEventListener("push", (event) => {
  if (!event.data) return;
  let data;
  try { data = event.data.json(); } catch { return; }

  const title = data.title || "Jawla";
  const options = {
    body: data.body || "",
    icon: "/icons/icon-192.png",
    badge: "/icons/icon-192.png",
    tag: data.tag || "jawla-push",
    data: data.url || "/app",
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  const url = event.notification.data || "/app";
  event.waitUntil(
    self.clients.matchAll({ type: "window" }).then((clients) => {
      for (const client of clients) {
        if (client.url.includes("/app") && "focus" in client) {
          return client.focus();
        }
      }
      return self.clients.openWindow(url);
    })
  );
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

  // Offline snapshot — cache-first. The client also stores this in IndexedDB,
  // but the SW cache gives instant responses and avoids a network round-trip.
  const url = new URL(event.request.url);
  if (
    url.origin === self.location.origin &&
    url.pathname === "/app/offline-snapshot"
  ) {
    event.respondWith(
      caches.match(event.request).then(
        (cached) =>
          cached ||
          fetch(event.request).then((response) => {
            if (!response.ok) return response;
            const copy = response.clone();
            event.waitUntil(
              caches
                .open(SNAPSHOT_CACHE)
                .then((cache) => cache.put(event.request, copy))
            );
            return response;
          })
      )
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
