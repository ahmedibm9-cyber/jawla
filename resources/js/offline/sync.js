// CG2 offline sync — drains the IndexedDB outbox to POST /app/sync and
// reconciles the per-operation results. Exposes window.jawlaSync for the rep
// flows (enqueue) and the sync-status UI (counts, retry, discard).

import * as outbox from "./outbox.js";
import * as cache from "./cache.js";

let flushing = false;
const identity = document.querySelector(
  'meta[name="jawla-offline-identity"]'
)?.content;

// Laravel sets a readable XSRF-TOKEN cookie on every web response and accepts it
// back via the X-XSRF-TOKEN header (it decrypts it). Using the cookie avoids
// needing a csrf <meta> in the layout. Falls back to a meta tag if present.
function xsrfToken() {
  const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
  if (m) return decodeURIComponent(m[1]);
  return (
    document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute("content") || ""
  );
}

async function emitStatus() {
  const items = await outbox.all();
  const pending = items.filter((i) => i.status === "pending").length;
  const failed = items.filter((i) => i.status === "failed").length;
  const conflicts = items.filter((i) => i.status === "conflict").length;
  window.dispatchEvent(
    new CustomEvent("jawla-sync-status", {
      detail: { pending, failed, conflicts },
    })
  );
}

async function emitStoragePressure() {
  const estimate = await outbox.checkQuota();
  if (!estimate) return null;

  window.dispatchEvent(
    new CustomEvent("jawla-storage-pressure", { detail: estimate })
  );

  return estimate;
}

function sortForFlush(items) {
  const tempIds = new Set(items.map((i) => i.tempId).filter(Boolean));
  const completed = new Set();
  const sorted = [];
  const rest = [...items];
  let lastLen = -1;

  while (rest.length > 0 && rest.length !== lastLen) {
    lastLen = rest.length;
    for (let i = 0; i < rest.length; i++) {
      const item = rest[i];
      if (!item.dependsOn || completed.has(item.dependsOn)) {
        sorted.push(item);
        if (item.tempId) completed.add(item.tempId);
        rest.splice(i, 1);
        i--;
      }
    }
  }
  return sorted;
}

async function flush() {
  if (flushing || !navigator.onLine) return;
  const items = await outbox.pending();
  if (!items.length) {
    await emitStatus();
    return;
  }

  const ordered = sortForFlush(items);
  if (!ordered.length) {
    await emitStatus();
    return;
  }

  flushing = true;
  try {
    const deviceId = outbox.getDeviceId();
    const res = await fetch("/app/sync", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-XSRF-TOKEN": xsrfToken(),
        "X-Requested-With": "XMLHttpRequest",
        "X-Sync-Protocol-Version": "1",
        "X-Device-Id": deviceId,
      },
      body: JSON.stringify({
        operations: ordered.map((i) => ({
          key: i.id,
          type: i.type,
          payload: i.payload,
          payloadHash: i.payloadHash,
          deviceId: i.deviceId,
        })),
      }),
    });

    // 401/419 → session/CSRF expired; keep everything queued and try later.
    if (!res.ok) return;

    const data = await res.json();
    for (const result of data.results || []) {
      if (result.status === "applied" || result.status === "duplicate") {
        await outbox.remove(result.key);
      } else if (result.status === "mismatch") {
        await outbox.markFailed(result.key, result.error || "Payload mismatch");
      } else if (["failed", "invalid", "unsupported"].includes(result.status)) {
        await outbox.markFailed(result.key, result.error || result.status);
      } else if (result.status === "conflict") {
        await outbox.markConflict(
          result.key,
          result.error || "Sync conflict requires support review."
        );
      }
    }
  } catch {
    // Network error — leave items pending for the next flush.
  } finally {
    flushing = false;
    await emitStatus();
    await emitStoragePressure();
  }
}

async function enqueue(type, payload, opts) {
  const record = await outbox.enqueue(type, payload, opts);
  await emitStatus();
  if (navigator.onLine) flush();
  // Request Background Sync so the SW flushes when connectivity returns,
  // even if the app isn't open. Supported in Chrome/Edge; no-op elsewhere.
  try {
    const reg = await navigator.serviceWorker?.getRegistration();
    if (reg?.sync) reg.sync.register("jawla-sync");
  } catch {
    /* sync not supported */
  }
  return record;
}

async function readEvidence(input) {
  const file = input?.files?.[0];
  if (!file) return null;
  if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
    throw new Error("Unsupported evidence format.");
  }
  if (file.size < 1 || file.size > 5 * 1024 * 1024) {
    throw new Error("Evidence must be between 1 byte and 5 MB.");
  }

  const dataUrl = await new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(reader.error);
    reader.readAsDataURL(file);
  });

  return {
    name: file.name,
    mime: file.type,
    base64: String(dataUrl).split(",", 2)[1],
  };
}

async function retry(id) {
  await outbox.markPending(id);
  await emitStatus();
  flush();
}

async function hasPending() {
  if (!window.indexedDB) return false;

  return (await outbox.all()).some((item) =>
    ["pending", "failed", "conflict"].includes(item.status)
  );
}

function syncQueueController() {
  return {
    items: [],
    pending: [],
    failed: [],
    conflicts: [],
    busy: false,
    loading: true,

    async init() {
      await this.reload();
      window.addEventListener("jawla-sync-status", () => this.reload());
    },

    async reload() {
      this.loading = true;
      try {
        this.items = await outbox.all();
        this.pending = this.items.filter((item) => item.status === "pending");
        this.failed = this.items.filter((item) => item.status === "failed");
        this.conflicts = this.items.filter(
          (item) => item.status === "conflict"
        );
      } finally {
        this.loading = false;
      }
    },

    async retryAll() {
      this.busy = true;
      try {
        await Promise.all(this.failed.map((item) => retry(item.id)));
        await flush();
      } finally {
        this.busy = false;
        await this.reload();
      }
    },

    async retryItem(id) {
      this.busy = true;
      try {
        await retry(id);
      } finally {
        this.busy = false;
        await this.reload();
      }
    },

    async discardItem(id) {
      this.busy = true;
      try {
        await outbox.remove(id);
        await emitStatus();
      } finally {
        this.busy = false;
        await this.reload();
      }
    },

    label(type) {
      return type.replaceAll("_", " ");
    },

    when(timestamp) {
      return new Intl.DateTimeFormat(document.documentElement.lang || "en", {
        dateStyle: "medium",
        timeStyle: "short",
      }).format(new Date(timestamp));
    },
  };
}

function init() {
  window.addEventListener("online", flush);
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) flush();
  });
  // Background Sync — SW tells us connectivity returned, flush now.
  navigator.serviceWorker?.addEventListener("message", (event) => {
    if (event.data?.type === "BACKGROUND_SYNC") flush();
  });
  emitStoragePressure();
  flush();
}

if (identity) {
  outbox.configureIdentity(identity);
  window.jawlaSync = {
    enqueue,
    flush,
    retry,
    discard: (id) => outbox.remove(id).then(emitStatus),
    all: outbox.all,
    pending: outbox.pending,
    failed: outbox.failed,
    hasPending,
    storageEstimate: emitStoragePressure,
    readEvidence,
  };
  window.jawlaSyncQueue = syncQueueController;
  window.jawlaOffline = {
    clear: async () => {
      await outbox.clear();
      await cache.clear();
      const registration = await navigator.serviceWorker?.getRegistration();
      registration?.active?.postMessage({ type: "PURGE_USER_DATA" });
      registration?.waiting?.postMessage({ type: "PURGE_USER_DATA" });
    },
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
}

export { sortForFlush as _testSortForFlush };
