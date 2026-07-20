// CG2 offline sync — drains the IndexedDB outbox to POST /app/sync and
// reconciles the per-operation results. Exposes window.jawlaSync for the rep
// flows (enqueue) and the sync-status UI (counts, retry, discard).

import * as outbox from "./outbox.js";

let flushing = false;

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
  window.dispatchEvent(
    new CustomEvent("jawla-sync-status", { detail: { pending, failed } })
  );
}

async function flush() {
  if (flushing || !navigator.onLine) return;
  const items = await outbox.pending();
  if (!items.length) {
    await emitStatus();
    return;
  }

  flushing = true;
  try {
    const res = await fetch("/app/sync", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-XSRF-TOKEN": xsrfToken(),
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        operations: items.map((i) => ({
          key: i.id,
          type: i.type,
          payload: i.payload,
        })),
      }),
    });

    // 401/419 → session/CSRF expired; keep everything queued and try later.
    if (!res.ok) return;

    const data = await res.json();
    for (const result of data.results || []) {
      if (result.status === "applied" || result.status === "duplicate") {
        await outbox.remove(result.key);
      } else if (["failed", "invalid", "unsupported"].includes(result.status)) {
        await outbox.markFailed(result.key, result.error || result.status);
      }
    }
  } catch {
    // Network error — leave items pending for the next flush.
  } finally {
    flushing = false;
    await emitStatus();
  }
}

async function enqueue(type, payload) {
  const record = await outbox.enqueue(type, payload);
  await emitStatus();
  if (navigator.onLine) flush();
  return record;
}

async function retry(id) {
  await outbox.markPending(id);
  await emitStatus();
  flush();
}

function init() {
  window.addEventListener("online", flush);
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) flush();
  });
  flush();
}

window.jawlaSync = {
  enqueue,
  flush,
  retry,
  discard: (id) => outbox.remove(id).then(emitStatus),
  all: outbox.all,
  pending: outbox.pending,
  failed: outbox.failed,
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
