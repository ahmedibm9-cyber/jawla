// Client-side IndexedDB cache for the offline data snapshot. Stores each
// dataset from GET /app/offline-snapshot so reps can browse read data
// without connectivity. Separate DB from the outbox (which handles writes).

const DB_NAME = "jawla-cache";
const DB_VERSION = 1;
const STORE = "datasets";
const META_STORE = "meta";
const SNAPSHOT_KEY = "offline-snapshot";
const STALE_MS = 24 * 60 * 60 * 1000; // 24h — refresh daily in background

function openDb() {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, DB_VERSION);
    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains(STORE)) {
        db.createObjectStore(STORE, { keyPath: "key" });
      }
      if (!db.objectStoreNames.contains(META_STORE)) {
        db.createObjectStore(META_STORE, { keyPath: "key" });
      }
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

function run(storeName, mode, fn) {
  return openDb().then(
    (db) =>
      new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, mode);
        const req = fn(tx.objectStore(storeName));
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
      })
  );
}

/**
 * Save a full snapshot to IndexedDB. Called after a successful fetch.
 * @param {object} snapshot - The full JSON response from /app/offline-snapshot
 */
export async function saveSnapshot(snapshot) {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction([STORE, META_STORE], "readwrite");
    const ds = tx.objectStore(STORE);
    const meta = tx.objectStore(META_STORE);

    // Store each dataset as its own record for granular access
    const datasets = [
      "customers",
      "products",
      "stock",
      "assignments",
      "pricing",
      "company",
      "tasks",
      "cashbox",
    ];
    for (const key of datasets) {
      if (snapshot[key] !== undefined) {
        ds.put({ key, data: snapshot[key], cachedAt: snapshot.cachedAt });
      }
    }

    meta.put({
      key: SNAPSHOT_KEY,
      cachedAt: snapshot.cachedAt,
      savedAt: Date.now(),
    });

    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
}

/**
 * Get a single dataset from cache.
 * @param {string} dataset - e.g. "customers", "products", "stock"
 * @returns {Promise<any|null>}
 */
export async function getDataset(dataset) {
  const record = await run(STORE, "readonly", (s) => s.get(dataset));
  return record?.data ?? null;
}

/**
 * Get the full cached snapshot (all datasets).
 * @returns {Promise<object|null>}
 */
export async function getSnapshot() {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction(STORE, "readonly");
    const req = tx.objectStore(STORE).getAll();
    req.onsuccess = () => {
      const records = req.result;
      if (!records.length) return resolve(null);
      const snapshot = {};
      for (const rec of records) {
        snapshot[rec.key] = rec.data;
      }
      resolve(snapshot);
    };
    req.onerror = () => reject(req.error);
  });
}

/**
 * Check if the cached snapshot is stale (older than STALE_MS).
 * @returns {Promise<boolean>}
 */
export async function isStale() {
  const meta = await run(META_STORE, "readonly", (s) => s.get(SNAPSHOT_KEY));
  if (!meta?.savedAt) return true;
  return Date.now() - meta.savedAt > STALE_MS;
}

/**
 * Get the cachedAt timestamp.
 * @returns {Promise<string|null>}
 */
export async function getCachedAt() {
  const meta = await run(META_STORE, "readonly", (s) => s.get(SNAPSHOT_KEY));
  return meta?.cachedAt ?? null;
}

/**
 * Fetch a fresh snapshot from the server and save it.
 * Returns the snapshot on success, null on failure.
 * @returns {Promise<object|null>}
 */
export async function refresh() {
  try {
    const res = await fetch("/app/offline-snapshot", {
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });
    if (!res.ok) return null;
    const snapshot = await res.json();
    await saveSnapshot(snapshot);
    return snapshot;
  } catch {
    return null;
  }
}

/**
 * Get the snapshot — from cache if available, refresh in background if stale.
 * Returns cached data immediately; triggers a background refresh if stale.
 * @returns {Promise<object|null>}
 */
export async function get() {
  const snapshot = await getSnapshot();
  if (snapshot && !(await isStale())) return snapshot;

  // Stale or missing — refresh in background, return whatever we have
  if (snapshot) {
    refresh(); // background, don't await
    return snapshot;
  }

  // No cache at all — must wait for network
  return refresh();
}

/**
 * Clear the entire cache (used on logout).
 */
export async function clear() {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const tx = db.transaction([STORE, META_STORE], "readwrite");
    tx.objectStore(STORE).clear();
    tx.objectStore(META_STORE).clear();
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
}

/**
 * Check if any cached data exists.
 * @returns {Promise<boolean>}
 */
export async function hasData() {
  const meta = await run(META_STORE, "readonly", (s) => s.get(SNAPSHOT_KEY));
  return !!meta;
}
