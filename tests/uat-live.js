const { chromium } = require("playwright");

const BASE = "https://jawla.up.railway.app";
const results = [];

async function test(name, fn) {
  try {
    await fn();
    results.push({ name, status: "PASS" });
    console.log(`✅ ${name}`);
  } catch (e) {
    results.push({ name, status: "FAIL", error: e.message });
    console.log(`❌ ${name}: ${e.message}`);
  }
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
  });

  // ── 1. Health Check ──
  await test("Health endpoint returns 200", async () => {
    const page = await context.newPage();
    const resp = await page.goto(`${BASE}/up`);
    if (resp.status() !== 200) throw new Error(`Status ${resp.status()}`);
    const text = await page.textContent("body");
    if (!text.includes("Application up"))
      throw new Error('Missing "Application up"');
    await page.close();
  });

  // ── 2. Login Page Loads ──
  await test("Login page renders", async () => {
    const page = await context.newPage();
    const resp = await page.goto(`${BASE}/admin/login`);
    if (resp.status() !== 200) throw new Error(`Status ${resp.status()}`);
    await page.waitForSelector('input[type="email"], input[name="email"]', {
      timeout: 10000,
    });
    await page.close();
  });

  // ── 3. Login as Admin ──
  await test("Admin login succeeds", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "admin@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/admin/**", { timeout: 15000 });
    const url = page.url();
    if (!url.includes("/admin")) throw new Error(`Still on ${url}`);
    await page.close();
  });

  // ── 4. Login as Rep ──
  await test("Rep login succeeds", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    const url = page.url();
    if (!url.includes("/app")) throw new Error(`Still on ${url}`);
    await page.close();
  });

  // ── 5. Login as Manager ──
  await test("Manager login succeeds", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "manager@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/admin/**", { timeout: 15000 });
    await page.close();
  });

  // ── 6. Rep PWA: Home Page ──
  await test("Rep home page loads", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    const content = await page.textContent("body");
    if (!content.includes("Jawla") && !content.includes("جولة"))
      throw new Error("Missing Jawla branding");
    await page.close();
  });

  // ── 7. Rep PWA: Todos Page ──
  await test("Todos page loads", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    await page.goto(`${BASE}/app/todos`);
    await page.waitForTimeout(3000);
    const content = await page.textContent("body");
    if (!content.includes("المهام") && !content.includes("Todos"))
      throw new Error("Missing todos content");
    await page.close();
  });

  // ── 8. Rep PWA: Tickets Page ──
  await test("Tickets page loads", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    await page.goto(`${BASE}/app/tickets`);
    await page.waitForTimeout(3000);
    const content = await page.textContent("body");
    if (!content.includes("التذاكر") && !content.includes("Tickets"))
      throw new Error("Missing tickets content");
    await page.close();
  });

  // ── 9. Rep PWA: Calendar Page ──
  await test("Calendar page loads", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    await page.goto(`${BASE}/app/calendar`);
    await page.waitForTimeout(3000);
    const content = await page.textContent("body");
    if (!content.includes("التقويم") && !content.includes("Calendar"))
      throw new Error("Missing calendar content");
    await page.close();
  });

  // ── 10. Rep PWA: Performance Dashboard ──
  await test("Performance dashboard loads", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    await page.goto(`${BASE}/app/performance`);
    await page.waitForTimeout(3000);
    const content = await page.textContent("body");
    if (!content.includes("الأداء") && !content.includes("Performance"))
      throw new Error("Missing performance content");
    await page.close();
  });

  // ── 11. Rep PWA: Agenda Page ──
  await test("Agenda page loads", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    await page.goto(`${BASE}/app/agenda`);
    await page.waitForTimeout(3000);
    const content = await page.textContent("body");
    if (!content.includes("الأجندة") && !content.includes("Agenda"))
      throw new Error("Missing agenda content");
    await page.close();
  });

  // ── 12. Rep PWA: Customer Summary Report ──
  await test("Customer summary report loads", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    await page.goto(`${BASE}/app/reports/customers`);
    await page.waitForTimeout(3000);
    const content = await page.textContent("body");
    if (!content.includes("ملخص") && !content.includes("Summary"))
      throw new Error("Missing customer summary");
    await page.close();
  });

  // ── 13. Rep PWA: More Page (all M7 links) ──
  await test("More page has all M7 links", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    await page.goto(`${BASE}/app/more`);
    await page.waitForTimeout(3000);
    const content = await page.textContent("body");
    const required = [
      "/app/todos",
      "/app/tickets",
      "/app/calendar",
      "/app/performance",
      "/app/reports/customers",
    ];
    for (const link of required) {
      if (!content.includes(link)) throw new Error(`Missing link: ${link}`);
    }
    await page.close();
  });

  // ── 14. No JavaScript errors on home page ──
  await test("No critical JS errors on rep home", async () => {
    const page = await context.newPage();
    const errors = [];
    page.on("pageerror", (err) => errors.push(err.message));
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });
    await page.waitForTimeout(5000);
    const critical = errors.filter(
      (e) => !e.includes("ResizeObserver") && !e.includes("favicon")
    );
    if (critical.length > 0)
      throw new Error(`JS errors: ${critical.join("; ")}`);
    await page.close();
  });

  // ── 15. Response times under 5s ──
  await test("All pages respond under 5 seconds", async () => {
    const page = await context.newPage();
    await page.goto(`${BASE}/admin/login`);
    await page.fill(
      'input[type="email"], input[name="email"]',
      "rep@jawla.test"
    );
    await page.fill(
      'input[type="password"], input[name="password"]',
      "123456789"
    );
    await page.click('button[type="submit"]');
    await page.waitForURL("**/app**", { timeout: 15000 });

    const pages = [
      "/app",
      "/app/todos",
      "/app/tickets",
      "/app/calendar",
      "/app/performance",
      "/app/agenda",
      "/app/more",
    ];
    for (const path of pages) {
      const start = Date.now();
      await page.goto(`${BASE}${path}`);
      await page.waitForLoadState("domcontentloaded");
      const elapsed = Date.now() - start;
      if (elapsed > 5000) throw new Error(`${path} took ${elapsed}ms`);
    }
    await page.close();
  });

  await browser.close();

  // ── Summary ──
  const passed = results.filter((r) => r.status === "PASS").length;
  const failed = results.filter((r) => r.status === "FAIL").length;
  console.log(`\n═══ UAT Results ═══`);
  console.log(`Total: ${results.length} | Pass: ${passed} | Fail: ${failed}`);
  if (failed > 0) {
    console.log(`\nFailed tests:`);
    results
      .filter((r) => r.status === "FAIL")
      .forEach((r) => console.log(`  ❌ ${r.name}: ${r.error}`));
  }
  process.exit(failed > 0 ? 1 : 0);
})();
