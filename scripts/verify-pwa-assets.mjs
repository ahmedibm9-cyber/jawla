import { readFile } from "node:fs/promises";
import { gzipSync } from "node:zlib";
import { resolve } from "node:path";

const buildDirectory = resolve("public/build");
const manifest = JSON.parse(
  await readFile(resolve(buildDirectory, "manifest.json"), "utf8")
);

const budgets = {
  ".js": 300 * 1024,
  ".css": 100 * 1024,
  total: 1.5 * 1024 * 1024,
};

const entries = Object.values(manifest).filter((entry) => entry.isEntry);
const totals = new Map(Object.keys(budgets).map((extension) => [extension, 0]));

for (const entry of entries) {
  const extension = Object.keys(budgets).find((candidate) =>
    entry.file.endsWith(candidate)
  );

  if (!extension) continue;

  const asset = await readFile(resolve(buildDirectory, entry.file));
  totals.set(extension, totals.get(extension) + gzipSync(asset).length);
}

const manifestAssets = [
  ...new Set(
    Object.values(manifest)
      .flatMap((entry) => [entry.file, ...(entry.assets ?? [])])
      .filter(Boolean)
  ),
];

for (const assetPath of manifestAssets) {
  const asset = await readFile(resolve(buildDirectory, assetPath));
  totals.set("total", totals.get("total") + gzipSync(asset).length);
}

let failed = false;

for (const [name, budget] of Object.entries(budgets)) {
  const size = totals.get(name) ?? 0;
  const status = size <= budget ? "PASS" : "FAIL";
  console.log(
    `${status} ${name}: ${(size / 1024).toFixed(1)} KiB gzip (budget ${(budget / 1024).toFixed(0)} KiB)`
  );
  failed ||= size > budget;
}

if (failed) {
  process.exitCode = 1;
}
