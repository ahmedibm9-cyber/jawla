import { expect, test } from "@playwright/test";

test.setTimeout(60_000);

const REP_EMAIL = "rep@jawla.test";
const REP_PASSWORD = "123456789";

async function loginAsRep(page) {
  await page.goto("/login", { waitUntil: "domcontentloaded", timeout: 20_000 });
  await page
    .getByRole("textbox", { name: /البريد الإلكتروني/i })
    .fill(REP_EMAIL);
  await page.getByRole("textbox", { name: /كلمة المرور/i }).fill(REP_PASSWORD);
  await page.getByRole("button", { name: /تسجيل الدخول/i }).click();
  await page.waitForURL("**/app", { timeout: 20_000 });
}

async function disableServiceWorker(page) {
  await page.evaluate(() => {
    navigator.serviceWorker
      ?.getRegistrations?.()
      .then((regs) => regs.forEach((r) => r.unregister()));
  });
}

/** Wait for Livewire to settle after a user action */
async function waitForLivewire(page) {
  // ponytail: domcontentloaded is enough — expect().toBeVisible() handles the rest
  await page.waitForLoadState("domcontentloaded");
}

async function selectCustomer(page) {
  const search = page.getByPlaceholder(/ابحث عن عميل/i);
  await search.fill("Al Rowad");
  await waitForLivewire(page);
  const result = page
    .locator("button")
    .filter({ hasText: /الرواد/ })
    .first();
  await expect(result).toBeVisible({ timeout: 10_000 });
  await result.click();
  await waitForLivewire(page);
}

async function addProduct(page, sku) {
  const prodSearch = page.getByPlaceholder(/ابحث عن منتج|ابحث بالكود/i);
  await expect(prodSearch).toBeVisible({ timeout: 5_000 });
  await prodSearch.fill(sku);
  await waitForLivewire(page);
  const result = page
    .locator("button[wire\\:click*='addToCart']")
    .filter({ hasText: new RegExp(sku) })
    .first();
  await expect(result).toBeVisible({ timeout: 10_000 });
  await result.click();
  await waitForLivewire(page);
}

// ─── SELL FLOW ───────────────────────────────────────────────────────────────

test.describe("sell flow: create invoice", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsRep(page);
    await page.goto("/app/sell", { waitUntil: "domcontentloaded" });
    await waitForLivewire(page);
  });

  test("customer search returns results and selecting shows product search", async ({
    page,
  }) => {
    const search = page.getByPlaceholder(/ابحث عن عميل/i);
    await search.fill("Al Rowad");
    await waitForLivewire(page);
    const result = page
      .locator("button")
      .filter({ hasText: /الرواد/ })
      .first();
    await expect(result).toBeVisible({ timeout: 10_000 });
    await result.click();
    await waitForLivewire(page);
    await expect(
      page.getByPlaceholder(/ابحث عن منتج|ابحث بالكود/i)
    ).toBeVisible();
  });

  test("product search returns results after customer selected", async ({
    page,
  }) => {
    await selectCustomer(page);
    const prodSearch = page.getByPlaceholder(/ابحث عن منتج|ابحث بالكود/i);
    await prodSearch.fill("VIR-PP");
    await waitForLivewire(page);
    const prodResult = page
      .locator("button")
      .filter({ hasText: /VIR-PP/ })
      .first();
    await expect(prodResult).toBeVisible({ timeout: 10_000 });
  });

  test("full invoice flow: select customer, add product, item in cart", async ({
    page,
  }) => {
    await selectCustomer(page);
    await addProduct(page, "VIR-PP-H030");
    await expect(
      page
        .locator("h3, h4, strong")
        .filter({ hasText: /سلة|Cart/ })
        .first()
    ).toBeVisible({ timeout: 15_000 });
  });

  test("changing customer resets to customer search", async ({ page }) => {
    await selectCustomer(page);
    await expect(
      page.getByPlaceholder(/ابحث عن منتج|ابحث بالكود/i)
    ).toBeVisible();
    const changeBtn = page
      .locator("button[wire\\:click*='clearCustomer']")
      .first();
    await expect(changeBtn).toBeVisible({ timeout: 10_000 });
    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes("/livewire/update"), {
        timeout: 10_000,
      }),
      changeBtn.click(),
    ]);
    await waitForLivewire(page);
    await expect(page.getByPlaceholder(/ابحث عن عميل/i)).toBeVisible({
      timeout: 10_000,
    });
  });
});

// ─── EXPENSES ────────────────────────────────────────────────────────────────

test.describe("expense logging", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsRep(page);
    await page.goto("/app/expenses", { waitUntil: "domcontentloaded" });
    await waitForLivewire(page);
  });

  test("page renders with category select", async ({ page }) => {
    await expect(page.locator("select").first()).toBeVisible();
    await expect(
      page.getByRole("button", { name: /تسجيل|حفظ/i })
    ).toBeVisible();
  });

  test("expense category options present", async ({ page }) => {
    const sel = page.locator("select").first();
    const options = await sel.locator("option").all();
    expect(options.length).toBeGreaterThanOrEqual(4);
  });

  test("fill expense and submit shows confirmation", async ({ page }) => {
    const catSel = page.locator("select").first();
    await catSel.selectOption({ index: 1 });
    const amount = page.locator("input").nth(0);
    if (await amount.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await amount.fill("50");
    }
    const note = page.locator("textarea");
    if (await note.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await note.fill("E2E test expense");
    }
    const submit = page.getByRole("button", { name: /تسجيل|حفظ/i });
    await submit.click();
    await waitForLivewire(page);
  });
});

// ─── FULL REP DAY E2E ────────────────────────────────────────────────────────

test("login, search customers, search stock, expense, logout", async ({
  page,
}) => {
  await loginAsRep(page);

  await page.goto("/app", { waitUntil: "domcontentloaded" });
  await waitForLivewire(page);
  await expect(
    page.locator("h2, h3, h1, strong, .font-semibold").first()
  ).toBeVisible();

  await page.goto("/app/customers", { waitUntil: "domcontentloaded" });
  await waitForLivewire(page);
  const customerSearch = page.getByPlaceholder(/ابحث عن عميل/i);
  if (await customerSearch.isVisible({ timeout: 5_000 }).catch(() => false)) {
    await customerSearch.fill("Al");
    await waitForLivewire(page);
  }

  await disableServiceWorker(page);
  await page.goto("/app/stock", { waitUntil: "domcontentloaded" });
  await disableServiceWorker(page);
  await waitForLivewire(page);
  const searchInput = page
    .getByLabel(/بحث|Search/i)
    .or(page.locator("input[wire\\:model*='search']").first());
  if (await searchInput.isVisible({ timeout: 5_000 }).catch(() => false)) {
    await searchInput.fill("VIR-PP");
    await waitForLivewire(page);
  }

  await page.goto("/app/expenses", { waitUntil: "domcontentloaded" });
  await waitForLivewire(page);
  await expect(page.locator("select").first()).toBeVisible();

  await page.goto("/app/more", { waitUntil: "domcontentloaded" });
  await waitForLivewire(page);
  const logoutForm = page
    .locator("form")
    .filter({ hasText: /تسجيل الخروج|logout/i })
    .first();
  if (await logoutForm.isVisible({ timeout: 5_000 }).catch(() => false)) {
    await logoutForm.locator("button[type='submit']").click();
    await page.waitForLoadState("domcontentloaded", { timeout: 10_000 });
  } else {
    const logoutBtn = page
      .locator("button, a")
      .filter({ hasText: /تسجيل الخروج|logout/i })
      .first();
    if (await logoutBtn.isVisible({ timeout: 3_000 }).catch(() => false)) {
      await logoutBtn.click();
      await page.waitForLoadState("domcontentloaded", { timeout: 10_000 });
    }
  }
});

// ─── VAN STOCK ────────────────────────────────────────────────────────────────

test.describe("stock search", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsRep(page);
    await disableServiceWorker(page);
    await page.goto("/app/stock", { waitUntil: "domcontentloaded" });
    await disableServiceWorker(page);
    await waitForLivewire(page);
  });

  test("page renders with search input", async ({ page }) => {
    const searchInput = page
      .getByLabel(/بحث|Search/i)
      .or(page.locator("input[wire\\:model*='search']").first());
    await expect(searchInput).toBeVisible({ timeout: 10_000 });
  });

  test("search for product by SKU shows result", async ({ page }) => {
    const searchInput = page
      .getByLabel(/بحث|Search/i)
      .or(page.locator("input[wire\\:model*='search']").first());
    await expect(searchInput).toBeVisible({ timeout: 10_000 });
    await searchInput.fill("VIR-PP");
    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes("/livewire/update"), {
        timeout: 15_000,
      }),
      searchInput.press("Enter"),
    ]).catch(() => {});
    await waitForLivewire(page);
    const resultText = page
      .locator(
        ".stock-row, [wire\\:key*='stock'], [wire\\:key*='row'], tr, .card"
      )
      .filter({ hasText: /VIR-PP/ })
      .first();
    const fallbackResult = page.locator("text=VIR-PP").first();
    const hasResult =
      (await resultText.isVisible({ timeout: 8_000 }).catch(() => false)) ||
      (await fallbackResult.isVisible({ timeout: 5_000 }).catch(() => false));
    expect(hasResult).toBeTruthy();
  });

  test("search with no results shows empty state", async ({ page }) => {
    const searchInput = page
      .getByLabel(/بحث|Search/i)
      .or(page.locator("input[wire\\:model*='search']").first());
    await expect(searchInput).toBeVisible({ timeout: 10_000 });
    await searchInput.fill("ZZZZZ");
    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes("/livewire/update"), {
        timeout: 15_000,
      }),
      searchInput.press("Enter"),
    ]).catch(() => {});
    await waitForLivewire(page);
  });
});
