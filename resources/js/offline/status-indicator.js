// Global offline-status indicator (UI/UX §55: clearly indicate offline status;
// §36.10 accurate status; §59.4 reduce anxiety). The sync engine already queues
// writes and auto-flushes on reconnect — this just tells the rep, honestly and
// unobtrusively, that they are currently offline and their work is being saved
// locally and will sync automatically. Toggles the [hidden] attribute so the
// element leaves the accessibility tree entirely when online.
function setup() {
  const el = document.getElementById("offline-indicator");
  if (!el) return;

  const apply = () => {
    el.hidden = navigator.onLine;
  };

  window.addEventListener("online", apply);
  window.addEventListener("offline", apply);
  apply();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", setup);
} else {
  setup();
}
