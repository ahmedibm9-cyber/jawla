// Global offline-status indicator (UI/UX §55: clearly indicate offline status;
// §36.10 accurate status; §59.4 reduce anxiety). The sync engine already queues
// writes and auto-flushes on reconnect — this just tells the rep, honestly and
// unobtrusively, that they are currently offline and their work is being saved
// locally and will sync automatically. Toggles the [hidden] attribute so the
// element leaves the accessibility tree entirely when online.
function setup() {
  const el = document.getElementById("offline-indicator");
  const storageEl = document.getElementById("storage-pressure-indicator");

  const apply = () => {
    if (el) {
      el.hidden = navigator.onLine;
    }
  };

  const applyStoragePressure = (estimate) => {
    if (!storageEl) return;

    const high = estimate?.pressure === "high";
    storageEl.hidden = !high;
    storageEl.dataset.percent = String(Math.round(estimate?.percent || 0));
  };

  window.addEventListener("online", apply);
  window.addEventListener("offline", apply);
  window.addEventListener("jawla-storage-pressure", (event) =>
    applyStoragePressure(event.detail)
  );
  apply();
  window.jawlaSync?.storageEstimate?.().then(applyStoragePressure);
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", setup);
} else {
  setup();
}
