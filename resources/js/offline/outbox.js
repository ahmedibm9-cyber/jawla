// CG2 offline outbox — a tiny IndexedDB queue for rep writes made while offline.
// No external dependency (hand-rolled per the project's package rule). Each
// record's `id` is a client-generated UUID used as the server idempotency key,
// so a replay is applied exactly once by the sync engine.

const DB_NAME = "jawla-offline";
const STORE = "outbox";
const VERSION = 1;

function openDb() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, VERSION);
    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains(STORE)) {
        const store = db.createObjectStore(STORE, { keyPath: "id" });
        store.createIndex("status", "status");
        store.createIndex("createdAt", "createdAt");
      }
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

function uuid() {
  if (crypto?.randomUUID) return crypto.randomUUID();
  return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    return (c === "x" ? r : (r & 0x3) | 0x8).toString(16);
  });
}

function run(store, mode, fn) {
  return openDb().then(
    (db) =>
      new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode);
        const req = fn(tx.objectStore(STORE));
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
      })
  );
}

export async function enqueue(type, payload) {
  const record = {
    id: uuid(),
    type,
    payload,
    status: "pending",
    error: null,
    createdAt: Date.now(),
  };
  await run(STORE, "readwrite", (s) => s.add(record));
  return record;
}

export function all() {
  return run(STORE, "readonly", (s) => s.getAll());
}

export async function pending() {
  return (await all()).filter((r) => r.status === "pending");
}

export async function failed() {
  return (await all()).filter((r) => r.status === "failed");
}

export function remove(id) {
  return run(STORE, "readwrite", (s) => s.delete(id));
}

async function setStatus(id, status, error = null) {
  const record = await run(STORE, "readonly", (s) => s.get(id));
  if (!record) return;
  record.status = status;
  record.error = error;
  await run(STORE, "readwrite", (s) => s.put(record));
}

export function markFailed(id, error) {
  return setStatus(id, "failed", error);
}

export function markPending(id) {
  return setStatus(id, "pending", null);
}
