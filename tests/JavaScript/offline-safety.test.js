import assert from "node:assert/strict";
import test from "node:test";

import {
  logoutMessage,
  prepareSafeLogout,
} from "../../resources/js/offline/logout-guard.js";
import { checkQuota } from "../../resources/js/offline/outbox.js";

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
