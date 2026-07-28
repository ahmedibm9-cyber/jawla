// Standalone test for sortForFlush — run with: node resources/js/offline/test-sort.js

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

let failed = 0;

function assert(cond, msg) {
  if (!cond) {
    console.error(`FAIL: ${msg}`);
    failed++;
  }
}

// Test 1: basic dependency ordering
const a = { id: "1", tempId: "A", dependsOn: null };
const b = { id: "2", tempId: null, dependsOn: "A" };
const r1 = sortForFlush([b, a]);
assert(r1[0].id === "1", "test1: A before B");
assert(r1[1].id === "2", "test1: B after A");

// Test 2: no dependencies → original order preserved
const x = { id: "x", tempId: null, dependsOn: null };
const y = { id: "y", tempId: null, dependsOn: null };
const r2 = sortForFlush([x, y]);
assert(r2[0].id === "x", "test2: x first");
assert(r2[1].id === "y", "test2: y second");

// Test 3: chain — C depends on B, B depends on A
const a3 = { id: "a", tempId: "TA", dependsOn: null };
const b3 = { id: "b", tempId: "TB", dependsOn: "TA" };
const c3 = { id: "c", tempId: null, dependsOn: "TB" };
const r3 = sortForFlush([c3, b3, a3]);
assert(r3.map((i) => i.id).join(",") === "a,b,c", "test3: chain order");

// Test 4: unresolvable dependency → item left at end (skipped)
const d = { id: "d", tempId: null, dependsOn: "MISSING" };
const e = { id: "e", tempId: null, dependsOn: null };
const r4 = sortForFlush([d, e]);
assert(r4.length === 1, "test4: unresolvable dep skipped");
assert(r4[0].id === "e", "test4: only e sent");

// Test 5: mixed — some with deps, some without
const m1 = { id: "m1", tempId: "T1", dependsOn: null };
const m2 = { id: "m2", tempId: "T2", dependsOn: "T1" };
const m3 = { id: "m3", tempId: null, dependsOn: null };
const m4 = { id: "m4", tempId: null, dependsOn: "T2" };
const r5 = sortForFlush([m4, m2, m3, m1]);
assert(r5[0].id === "m3", "test5: m3 first (no dep)");
assert(r5[1].id === "m1", "test5: m1 second (no dep)");
assert(r5[2].id === "m2", "test5: m2 third (depends on T1, resolved)");
assert(r5[3].id === "m4", "test5: m4 last (depends on T2, resolved)");

if (failed === 0) {
  console.log("All tests passed.");
} else {
  console.error(`${failed} test(s) failed.`);
  process.exit(1);
}
