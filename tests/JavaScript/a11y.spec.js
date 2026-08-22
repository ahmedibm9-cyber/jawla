import AxeBuilder from "@axe-core/playwright";
import { expect, test } from "@playwright/test";

test.describe("accessibility audits", () => {
  test("login page passes axe-core checks", async ({ page }) => {
    await page.goto("/login", { waitUntil: "domcontentloaded" });

    const results = await new AxeBuilder({ page })
      .exclude("[data-testid='skip-a11y']")
      .analyze();

    expect(results.violations).toEqual([]);
  });

  test("login page in Arabic passes axe-core checks", async ({ page }) => {
    await page.goto("/locale/ar");
    await page.waitForLoadState("domcontentloaded");

    const results = await new AxeBuilder({ page })
      .exclude("[data-testid='skip-a11y']")
      .analyze();

    expect(results.violations).toEqual([]);
  });

  test("rep home page passes axe-core checks", async ({ page }) => {
    const REP_EMAIL = "rep@jawla.test";
    const REP_PASSWORD = "123456789";

    await page.goto("/login", { waitUntil: "domcontentloaded" });
    await page
      .getByRole("textbox", { name: /البريد الإلكتروني/i })
      .fill(REP_EMAIL);
    await page
      .getByRole("textbox", { name: /كلمة المرور/i })
      .fill(REP_PASSWORD);
    await page.getByRole("button", { name: /تسجيل الدخول/i }).click();
    await page.waitForURL("**/app", { timeout: 20_000 });

    const results = await new AxeBuilder({ page })
      .exclude("[data-testid='skip-a11y']")
      .analyze();

    expect(results.violations).toEqual([]);
  });

  test("login page passes color contrast checks", async ({ page }) => {
    await page.goto("/login", { waitUntil: "domcontentloaded" });

    const results = await new AxeBuilder({ page })
      .withRules(["color-contrast"])
      .exclude("[data-testid='skip-a11y']")
      .analyze();

    expect(results.violations).toEqual([]);
  });

  test("login page works at 200% zoom without horizontal scroll", async ({
    page,
  }) => {
    await page.goto("/login", { waitUntil: "domcontentloaded" });
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.evaluate(() => {
      document.documentElement.style.zoom = "2";
    });
    await page.waitForTimeout(300);

    const scrollWidth = await page.evaluate(
      () => document.documentElement.scrollWidth
    );
    const viewportWidth = await page.evaluate(() => window.innerWidth);

    expect(scrollWidth).toBeLessThanOrEqual(viewportWidth + 10);
  });

  test("login page works at 400% zoom without horizontal scroll", async ({
    page,
  }) => {
    await page.goto("/login", { waitUntil: "domcontentloaded" });
    await page.setViewportSize({ width: 1280, height: 720 });
    await page.evaluate(() => {
      document.documentElement.style.zoom = "4";
    });
    await page.waitForTimeout(300);

    const scrollWidth = await page.evaluate(
      () => document.documentElement.scrollWidth
    );
    const viewportWidth = await page.evaluate(() => window.innerWidth);

    // At 400% zoom, content should reflow — allow 10px tolerance for subpixel rounding
    expect(scrollWidth).toBeLessThanOrEqual(viewportWidth + 10);
  });

  test("login page in Arabic passes color contrast checks", async ({
    page,
  }) => {
    await page.goto("/locale/ar");
    await page.waitForLoadState("domcontentloaded");

    const results = await new AxeBuilder({ page })
      .withRules(["color-contrast"])
      .exclude("[data-testid='skip-a11y']")
      .analyze();

    expect(results.violations).toEqual([]);
  });
});
