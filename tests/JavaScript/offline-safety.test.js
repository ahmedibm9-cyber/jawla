import assert from "node:assert/strict";
import test from "node:test";

import {
  logoutMessage,
  prepareSafeLogout,
} from "../../resources/js/offline/logout-guard.js";
import { checkQuota } from "../../resources/js/offline/outbox.js";
import { enqueue } from "../../resources/js/offline/sync.js";

test("logout is blocked without clearing when offline operations remain", async () => {
  let cleared = false;

  const result = await prepareSafeLogout({
    sync: { hasPending: async () => true },
    offline: {
      clear: async () => {
        cleared = true;
      },
    },
  });

  assert.deepEqual(result, { allowed: false, reason: "pending" });
  assert.equal(cleared, false);
});

test("logout clears user caches only after the queue is empty", async () => {
  let cleared = false;

  const result = await prepareSafeLogout({
    sync: { hasPending: async () => false },
    offline: {
      clear: async () => {
        cleared = true;
      },
    },
  });

  assert.deepEqual(result, { allowed: true, reason: null });
  assert.equal(cleared, true);
});

test("logout fails closed when offline safety verification errors", async () => {
  const result = await prepareSafeLogout({
    sync: {
      hasPending: async () => {
        throw new Error("IndexedDB failed");
      },
    },
    offline: {
      clear: async () => {
        throw new Error("must not run");
      },
    },
  });

  assert.deepEqual(result, {
    allowed: false,
    reason: "verification-failed",
  });
});

test("logout fails closed when the sync safety checker is unavailable", async () => {
  let cleared = false;

  const result = await prepareSafeLogout({
    sync: null,
    offline: {
      clear: async () => {
        cleared = true;
      },
    },
  });

  assert.deepEqual(result, {
    allowed: false,
    reason: "verification-failed",
  });
  assert.equal(cleared, false);
});

test("logout warnings are bilingual", () => {
  assert.match(logoutMessage("en", "pending"), /unsynced sales/);
  assert.match(logoutMessage("ar", "pending"), /غير متزامنة/);
});

test("storage pressure never mutates or deletes queued operations", async () => {
  let estimates = 0;
  const result = await checkQuota(async () => {
    estimates += 1;
    return { usage: 90, quota: 100 };
  });

  assert.equal(estimates, 1);
  assert.deepEqual(result, {
    used: 90,
    quota: 100,
    percent: 90,
    pressure: "high",
  });
});

test("storage pressure uses stable threshold boundaries", async () => {
  const atSixtyPercent = await checkQuota(async () => ({
    usage: 60,
    quota: 100,
  }));
  const atEightyPercent = await checkQuota(async () => ({
    usage: 80,
    quota: 100,
  }));
  const justAboveEightyPercent = await checkQuota(async () => ({
    usage: 81,
    quota: 100,
  }));

  assert.equal(atSixtyPercent.pressure, "normal");
  assert.equal(atEightyPercent.pressure, "medium");
  assert.equal(justAboveEightyPercent.pressure, "high");
});

test("storage pressure is unavailable rather than crashing without Storage API", async () => {
  assert.equal(await checkQuota(), null);
});

test("offline sale is queued for sync", async () => {
  const result = await enqueue("sale", {
    customerId: "CUST-001",
    visitId: "VISIT-001",
    items: [{ productId: "PROD-001", quantity: 2 }],
  });

  assert.equal(result.status, "queued");
  assert.equal(result.offline, true);
});

test("offline sale data persists in IndexedDB after reconnect", async () => {
  // Test that offline data is preserved in IndexedDB
  const db = await indexedDB.open("jawla-cache");

  db.onupgradeneeded = (event) => {
    const database = event.target.result;
    if (!database.objectStoreNames.contains("offline-sales")) {
      database.createObjectStore("offline-sales");
    }
  };

  await new Promise((resolve) => (db.onerror = resolve));

  const transaction = db.transaction("offline-sales", "readwrite");
  const store = transaction.objectStore("offline-sales");
  store.put(
    {
      soldAt: new Date().toISOString(),
      items: [{ productId: "PROD-001", quantity: 2 }],
    },
    "sale-1"
  );

  await new Promise((resolve) => (transaction.oncomplete = resolve));

  const retrieveTransaction = db.transaction("offline-sales", "readonly");
  const retrieveStore = retrieveTransaction.objectStore("offline-sales");
  const retrieved = retrieveStore.get("sale-1");

  retrieve.onsuccess = () => {
    assert.equal(retrieved.result.items[0].productId, "PROD-001");
    assert.equal(retrieved.result.items[0].quantity, 2);
  };

  await new Promise((resolve) => (retrieve.onsuccess = resolve));
  db.close();
});

test("offline sale queuing rejects insufficient stock", async () => {
  const result = await enqueue("sale", {
    customerId: "CUST-001",
    visitId: "VISIT-001",
    items: [{ productId: "PROD-001", quantity: 5 }],
    stockCheck: async () => ({ available: 2, reserved: 0 }),
  });

  assert.equal(result.status, "rejected");
  assert.equal(result.reason, "insufficient-stock");
  assert.equal(result.available, 2);
});

test("offline sale queuing validates price positivity", async () => {
  const result = await enqueue("sale", {
    customerId: "CUST-001",
    visitId: "VISIT-001",
    items: [{ productId: "PROD-001", quantity: 1 }],
    prices: { "PROD-001": -5 },
  });

  assert.equal(result.status, "rejected");
  assert.equal(result.reason, "invalid-price");
});
