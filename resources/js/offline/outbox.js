// CG2 offline outbox — a tiny IndexedDB queue for rep writes made while offline.
// No external dependency (hand-rolled per the project's package rule). Each
// record's `id` is a client-generated UUID used as the server idempotency key,
// so a replay is applied exactly once by the sync engine.

const DB_PREFIX = "jawla-offline-";
const STORE = "outbox";
export const OUTBOX_DB_VERSION = 2;
const DEVICE_ID_KEY = "jawla-device-id";
let identity = null;

export function getDeviceId() {
  let id = localStorage.getItem(DEVICE_ID_KEY);
  if (!id) {
    id = uuid();
    localStorage.setItem(DEVICE_ID_KEY, id);
  }
  return id;
}

export function configureIdentity(value) {
  if (!value || typeof value !== "string") {
    throw new Error("An authenticated offline identity is required.");
  }

  identity = value;
}

function dbName() {
  if (!identity) {
    throw new Error(
      "Offline storage has not been scoped to an authenticated user."
    );
  }

  return `${DB_PREFIX}${identity}`;
}

function openDb() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(dbName(), OUTBOX_DB_VERSION);
    req.onupgradeneeded = (event) => {
      const db = req.result;
      const tx = req.transaction;
      if (!db.objectStoreNames.contains(STORE)) {
        const store = db.createObjectStore(STORE, { keyPath: "id" });
        store.createIndex("status", "status");
        store.createIndex("createdAt", "createdAt");
      } else if (event.oldVersion < 2) {
        const store = tx.objectStore(STORE);
        store.openCursor().onsuccess = (e) => {
          const cursor = e.target.result;
          if (!cursor) return;
          cursor.value.schemaVersion = 2;
          cursor.update(cursor.value);
          cursor.continue();
        };
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

async function sha256Hex(str) {
  const data = new TextEncoder().encode(str);
  const hash = await crypto.subtle.digest("SHA-256", data);
  return [...new Uint8Array(hash)]
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

export async function enqueue(
  type,
  payload,
  { dependsOn = null, tempId = null } = {}
) {
  const payloadHash = await sha256Hex(JSON.stringify({ type, payload }));
  const record = {
    id: uuid(),
    type,
    payload,
    payloadHash,
    deviceId: getDeviceId(),
    status: "pending",
    error: null,
    createdAt: Date.now(),
    dependsOn,
    tempId,
    schemaVersion: OUTBOX_DB_VERSION,
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

export function markConflict(id, error) {
  return setStatus(id, "conflict", error);
}

export function clear() {
  if (!identity || !window.indexedDB) return Promise.resolve();

  return run(STORE, "readwrite", (store) => store.clear());
}

export async function hasStaleRecords() {
  if (!identity || !window.indexedDB) return false;
  const records = await pending();
  return records.some(
    (r) => !r.schemaVersion || r.schemaVersion < OUTBOX_DB_VERSION
  );
}

export async function oldestFailed() {
  const items = await failed();
  if (!items.length) return null;
  return items.sort((a, b) => a.createdAt - b.createdAt)[0];
}

export async function checkQuota() {
  if (!navigator.storage?.estimate) return null;

  let { usage, quota } = await navigator.storage.estimate();
  let used = usage || 0;
  let total = quota || 0;
  let percent = total ? (used / total) * 100 : 0;

  if (percent > 80) {
    let oldest = await oldestFailed();
    while (oldest && percent > 80) {
      await remove(oldest.id);
      ({ usage, quota } = await navigator.storage.estimate());
      used = usage || 0;
      total = quota || 0;
      percent = total ? (used / total) * 100 : 0;
      oldest = await oldestFailed();
    }
  }

  return { used, quota: total, percent };
}
