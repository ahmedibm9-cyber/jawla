import { expect, test } from "@playwright/test";

test.setTimeout(60_000);

const REP_EMAIL = "rep@jawla.test";
const REP_PASSWORD = "123456789";

/** Helper: log in as rep and land on /app */
async function loginAsRep(page) {
  await page.goto("/login", { waitUntil: "domcontentloaded", timeout: 20_000 });
  await page
    .getByRole("textbox", { name: /البريد الإلكتروني/i })
    .fill(REP_EMAIL);
  await page.getByRole("textbox", { name: /كلمة المرور/i }).fill(REP_PASSWORD);
  await page.getByRole("button", { name: /تسجيل الدخول/i }).click();
  await page.waitForURL("**/app", { timeout: 20_000 });
}

/** Helper: navigate and wait for Livewire to settle */
async function gotoLivewire(page, path) {
  await page.goto(path, { waitUntil: "domcontentloaded" });
}

test.describe("rep login", () => {
  test("rep login redirects to /app with welcome message", async ({ page }) => {
    await loginAsRep(page);

    await expect(page.locator("h1")).toContainText(/مرحبا/);
  });

  test("rep login shows the bottom tab bar", async ({ page }) => {
    await loginAsRep(page);

    const nav = page.getByRole("navigation", { name: /bottom/i });
    await expect(nav).toBeVisible();
    await expect(nav.getByRole("link", { name: /الرئيسية/i })).toBeVisible();
    await expect(nav.getByRole("link", { name: /الزيارات/i })).toBeVisible();
    await expect(nav.getByRole("link", { name: /العملاء/i })).toBeVisible();
    await expect(nav.getByRole("link", { name: /الطلبات/i })).toBeVisible();
    await expect(nav.getByRole("link", { name: /المزيد/i })).toBeVisible();
  });

  test("wrong password stays on login page", async ({ page }) => {
    await page.goto("/login", { waitUntil: "domcontentloaded" });
    await page
      .getByRole("textbox", { name: /البريد الإلكتروني/i })
      .fill(REP_EMAIL);
    await page
      .getByRole("textbox", { name: /كلمة المرور/i })
      .fill("wrong-password");
    await page.getByRole("button", { name: /تسجيل الدخول/i }).click();

    await page.waitForLoadState("networkidle");
    await expect(page).toHaveURL(/\/login/);
  });
});

test.describe("rep home page", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsRep(page);
  });

  test("home shows hero with user name and stats", async ({ page }) => {
    await expect(page.locator("h1")).toContainText(/مرحبا/);
    await expect(page.locator("h1")).toContainText(/أحمد/);
  });

  test("home shows today's plan section", async ({ page }) => {
    await expect(page.getByRole("heading", { level: 3 })).toContainText(
      /خطة اليوم/
    );
  });

  test("home shows start day button", async ({ page }) => {
    await expect(
      page.getByRole("button", { name: /بدء اليوم/i })
    ).toBeVisible();
  });

  test("home shows quick action links", async ({ page }) => {
    await expect(
      page.getByRole("link", { name: /فاتورة جديدة/i })
    ).toBeVisible();
    await expect(
      page.getByRole("link", { name: /تأكيد الوصول/i })
    ).toBeVisible();
  });

  test("clicking new invoice navigates to sell page", async ({ page }) => {
    await page.getByRole("link", { name: /فاتورة جديدة/i }).click();
    await expect(page).toHaveURL(/\/app\/sell/);
  });

  test("clicking confirm arrival navigates to visits page", async ({
    page,
  }) => {
    await page.getByRole("link", { name: /تأكيد الوصول/i }).click();
    await expect(page).toHaveURL(/\/app\/visits/);
  });

  test("home has no horizontal overflow at mobile width", async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await gotoLivewire(page, "/app");

    const scrollWidth = await page.evaluate(
      () => document.documentElement.scrollWidth
    );
    const clientWidth = await page.evaluate(
      () => document.documentElement.clientWidth
    );
    expect(scrollWidth).toBeLessThanOrEqual(clientWidth);
  });
});

test.describe("rep tab navigation", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsRep(page);
  });

  test("visits tab loads visit history", async ({ page }) => {
    await gotoLivewire(page, "/app/visits");

    await expect(page.getByRole("heading", { name: /الزيارات/ })).toBeVisible();
    await expect(
      page.getByText(/الاثنين|الثلاثاء|الأربعاء|الخميس|الجمعة|السبت|الأحد/i)
    ).toBeVisible();
  });

  test("customers tab loads customer list with search", async ({ page }) => {
    await gotoLivewire(page, "/app/customers");

    await expect(page.getByText("العملاء").first()).toBeVisible();
    await expect(page.getByPlaceholder(/ابحث بالاسم أو الهاتف/i)).toBeVisible();
    await expect(page.getByRole("link", { name: /إضافة عميل/i })).toBeVisible();
  });

  test("customers search input is interactive", async ({ page }) => {
    await gotoLivewire(page, "/app/customers");

    const search = page.getByPlaceholder(/ابحث بالاسم أو الهاتف/i);
    await search.fill("test");
    await expect(search).toHaveValue("test");
  });

  test("orders tab loads with sub-tabs", async ({ page }) => {
    await gotoLivewire(page, "/app/orders");

    await expect(page.getByRole("tab", { name: /أوامر البيع/ })).toBeVisible();
    await expect(
      page.getByRole("tab", { name: "الفواتير", exact: true })
    ).toBeVisible();
    await expect(
      page.getByRole("tab", { name: /الفواتير المبدئية/ })
    ).toBeVisible();
    await expect(page.getByRole("tab", { name: /عروض الشراء/ })).toBeVisible();
  });

  test("orders shows invoice cards with amounts", async ({ page }) => {
    await gotoLivewire(page, "/app/orders");

    await expect(page.getByText(/EGP/).first()).toBeVisible();
    await expect(page.getByText(/INV-/).first()).toBeVisible();
  });

  test("more tab shows profile and action tiles", async ({ page }) => {
    await gotoLivewire(page, "/app/more");

    await expect(page.getByText("أحمد سعيد")).toBeVisible();
    await expect(page.getByText("EMP-007")).toBeVisible();
    await expect(page.getByRole("link", { name: /المهام/i })).toBeVisible();
    await expect(page.getByRole("link", { name: /إضافة عميل/i })).toBeVisible();
    await expect(page.getByRole("link", { name: /تحصيل دفعة/i })).toBeVisible();
    await expect(
      page.getByRole("link", { name: /تسجيل مصروف/i })
    ).toBeVisible();
  });

  test("more tab shows cash box and van stock stats", async ({ page }) => {
    await gotoLivewire(page, "/app/more");

    await expect(page.getByText("الصندوق")).toBeVisible();
    await expect(page.getByText("مخزون العربة")).toBeVisible();
  });

  test("more tab has logout button", async ({ page }) => {
    await gotoLivewire(page, "/app/more");

    await expect(
      page.getByRole("button", { name: /تسجيل الخروج/i })
    ).toBeVisible();
  });
});

test.describe("rep sell page (invoice creation)", () => {
  test.beforeEach(async ({ page }) => {
    await loginAsRep(page);
  });

  test("sell page shows 2-step stepper", async ({ page }) => {
    await gotoLivewire(page, "/app/sell");

    await expect(page.locator("h1, h2, h3")).toContainText(/فاتورة جديدة/);
    await expect(page.getByText("تم")).toBeVisible();
    await expect(page.getByText("السلة")).toBeVisible();
  });

  test("sell page has customer search", async ({ page }) => {
    await gotoLivewire(page, "/app/sell");

    await expect(page.getByPlaceholder(/ابحث عن عميل/i)).toBeVisible();
  });

  test("sell page has product search", async ({ page }) => {
    await gotoLivewire(page, "/app/sell");

    await expect(page.getByPlaceholder(/ابحث عن منتج/i)).toBeVisible();
  });

  test("sell page has barcode scan and manual entry buttons", async ({
    page,
  }) => {
    await gotoLivewire(page, "/app/sell");

    await expect(
      page.getByRole("button", { name: /مسح باركود/i })
    ).toBeVisible();
    await expect(
      page.getByRole("button", { name: /إدخال يدوي/i })
    ).toBeVisible();
  });
});

test.describe("rep RTL and accessibility", () => {
  test("app renders in RTL direction", async ({ page }) => {
    await loginAsRep(page);

    await expect(page.locator("html")).toHaveAttribute("dir", "rtl");
    await expect(page.locator("html")).toHaveAttribute("lang", "ar");
  });

  test("all interactive controls are named", async ({ page }) => {
    await loginAsRep(page);

    const unnamedControls = await page
      .locator(
        'button:visible, a[href]:visible, input:not([type="hidden"]):visible, [role="button"]:visible'
      )
      .evaluateAll((controls) =>
        controls
          .filter((el) => {
            const text = [
              el.getAttribute("aria-label"),
              el.getAttribute("title"),
              el.textContent,
            ]
              .filter(Boolean)
              .join(" ")
              .trim();
            return text.length === 0;
          })
          .map((el) => ({
            tag: el.tagName.toLowerCase(),
            html: el.outerHTML.slice(0, 200),
          }))
      );

    expect(
      unnamedControls,
      `unnamed controls: ${JSON.stringify(unnamedControls)}`
    ).toEqual([]);
  });

  test("each page has no horizontal overflow at 375px", async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await loginAsRep(page);

    for (const path of [
      "/app",
      "/app/customers",
      "/app/visits",
      "/app/orders",
      "/app/more",
      "/app/sell",
    ]) {
      await gotoLivewire(page, `http://127.0.0.1:8765${path}`);

      const scrollWidth = await page.evaluate(
        () => document.documentElement.scrollWidth
      );
      const clientWidth = await page.evaluate(
        () => document.documentElement.clientWidth
      );
      expect(
        scrollWidth,
        `overflow on ${path}: scrollWidth=${scrollWidth} > clientWidth=${clientWidth}`
      ).toBeLessThanOrEqual(clientWidth);
    }
  });
});

test.describe("rep logout", () => {
  test("logout from more page returns to login", async ({ page }) => {
    await loginAsRep(page);
    await gotoLivewire(page, "/app/more");

    await page.getByRole("button", { name: /تسجيل الخروج/i }).click();
    // Form POST may cause ERR_ABORTED — catch and wait for domcontentloaded
    await page.waitForURL("**/login", { timeout: 15_000 }).catch(() => {});
    await page.waitForLoadState("domcontentloaded");

    await expect(page).toHaveURL(/\/login/);
    await expect(
      page.getByRole("button", { name: /تسجيل الدخول/i })
    ).toBeVisible();
  });

  test("after logout, /app redirects to login", async ({ page }) => {
    await loginAsRep(page);
    await gotoLivewire(page, "/app/more");

    // Form POST causes ERR_ABORTED — catch it and wait for the redirect
    await page.getByRole("button", { name: /تسجيل الخروج/i }).click();
    await Promise.all([page.waitForURL("**/login", { timeout: 15_000 })]).catch(
      () => {}
    );
    await page.waitForLoadState("domcontentloaded");

    await page.goto("/app", { waitUntil: "domcontentloaded" });
    await expect(page).toHaveURL(/\/login/);
  });
});

test.describe("rep end-to-end journey", () => {
  test("full rep day: login → home → customers → orders → sell → more → logout", async ({
    page,
  }) => {
    // 1. Login
    await loginAsRep(page);
    await expect(page.locator("h1")).toContainText(/مرحبا/);

    // 2. Home page checks
    await expect(
      page.getByRole("button", { name: /بدء اليوم/i })
    ).toBeVisible();
    await expect(
      page.getByRole("link", { name: /فاتورة جديدة/i })
    ).toBeVisible();

    // 3. Customers
    await gotoLivewire(page, "/app/customers");
    await expect(page.getByPlaceholder(/ابحث بالاسم أو الهاتف/i)).toBeVisible();

    // 4. Orders
    await gotoLivewire(page, "/app/orders");
    await expect(
      page.getByRole("tab", { name: "الفواتير", exact: true }).first()
    ).toBeVisible();

    // 5. Sell
    await gotoLivewire(page, "/app/sell");
    await expect(page.getByPlaceholder(/ابحث عن عميل/i)).toBeVisible();

    // 6. More
    await gotoLivewire(page, "/app/more");
    await expect(page.getByText("أحمد سعيد")).toBeVisible();

    // 7. Logout — form POST causes ERR_ABORTED, catch and verify redirect
    await page.getByRole("button", { name: /تسجيل الخروج/i }).click();
    await Promise.all([page.waitForURL("**/login", { timeout: 15_000 })]).catch(
      () => {}
    );
    await page.waitForLoadState("domcontentloaded");
    await page.goto("/app", { waitUntil: "domcontentloaded" });
    await expect(page).toHaveURL(/\/login/);
  });
});
