import { expect, test } from "@playwright/test";

test.describe("public PWA readiness", () => {
  test("manifest, icons, and service worker are installable", async ({
    page,
    request,
  }) => {
    const manifestResponse = await request.get("/manifest.json");
    expect(manifestResponse.ok()).toBeTruthy();

    const manifest = await manifestResponse.json();
    expect(manifest.name).toBeTruthy();
    expect(manifest.short_name).toBeTruthy();
    expect(manifest.start_url).toBeTruthy();
    expect(manifest.display).toBe("standalone");
    expect(manifest.icons.length).toBeGreaterThanOrEqual(2);

    for (const icon of manifest.icons) {
      const iconResponse = await request.get(icon.src);
      expect(
        iconResponse.ok(),
        `missing manifest icon ${icon.src}`
      ).toBeTruthy();
    }

    await page.goto("/login");
    await expect(page.locator('link[rel="manifest"]')).toHaveAttribute(
      "href",
      "/manifest.json"
    );
    await expect(page.locator('meta[name="theme-color"]')).toHaveAttribute(
      "content",
      "#0F172A"
    );
    await expect(page.locator('link[rel="apple-touch-icon"]')).toHaveAttribute(
      "href",
      "/icons/icon-192.png"
    );
    await expect(
      page.locator('script[src$="/js/pwa-login-register.js"]')
    ).toHaveCount(1);
    const scope = await page.evaluate(async () => {
      const registration = await navigator.serviceWorker.ready;
      return registration.scope;
    });

    expect(new URL(scope).pathname).toBe("/");
  });

  test("login is keyboard-readable in English and Arabic", async ({ page }) => {
    await page.goto("/locale/en");
    await expect(page.locator("html")).toHaveAttribute("lang", "en");
    await expect(page.locator("html")).toHaveAttribute("dir", "ltr");
    await expect(page.getByRole("textbox").first()).toBeVisible();
    await expect(page.getByRole("button").first()).toBeVisible();

    const englishTree = await page.locator("body").ariaSnapshot();
    expect(englishTree).toContain("textbox");
    expect(englishTree).toContain("button");

    await page.goto("/locale/ar");
    await expect(page.locator("html")).toHaveAttribute("lang", "ar");
    await expect(page.locator("html")).toHaveAttribute("dir", "rtl");

    await page.keyboard.press("Tab");
    await expect(page.locator(":focus")).toBeVisible();
  });

  test("offline navigation returns the bilingual offline shell", async ({
    context,
    page,
  }) => {
    await page.goto("/login");
    await page.evaluate(() => navigator.serviceWorker.ready);
    await page.reload();
    await page.waitForFunction(() =>
      Boolean(navigator.serviceWorker.controller)
    );

    await context.setOffline(true);

    try {
      await page.goto("/app", { waitUntil: "domcontentloaded" });
      await expect(page.getByRole("heading", { level: 1 })).toContainText(
        /No Internet Connection|لا يوجد اتصال بالإنترنت/
      );
      await expect(page.getByRole("link")).toContainText(
        /Retry|إعادة المحاولة/
      );
      await expect(page.getByRole("link")).toHaveAttribute("href", "/app");
    } finally {
      await context.setOffline(false);
    }
  });

  test("login remains usable without horizontal overflow on a narrow screen", async ({
    page,
  }) => {
    await page.setViewportSize({ width: 360, height: 800 });
    await page.goto("/locale/en");

    await expect(page.getByRole("textbox").first()).toBeVisible();
    await expect(page.getByRole("button").first()).toBeVisible();
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= window.innerWidth
      )
    ).toBeTruthy();
  });

  test("public login meets the local response-time budget", async ({
    page,
  }) => {
    await page.goto("/login", { waitUntil: "load" });
    const duration = await page.evaluate(() => {
      const navigation = performance.getEntriesByType("navigation")[0];
      return navigation ? navigation.duration : Number.POSITIVE_INFINITY;
    });

    expect(duration).toBeLessThan(5_000);
  });
});
