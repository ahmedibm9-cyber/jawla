// Jawla service worker — app-shell cache only in v1.
// Full offline data sync is out of scope for v1 (see main guide §12).
const CACHE = 'jawla-shell-v1';
const SHELL = ['/', '/app', '/manifest.json'];
self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(SHELL)));
});
self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  e.respondWith(caches.match(e.request).then(r => r || fetch(e.request)));
});
