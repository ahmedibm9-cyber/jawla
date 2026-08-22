import { expect, test } from "@playwright/test";

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
  await page.waitForLoadState("domcontentloaded");
}

test.describe("offline PWA flow", () => {
  test("service worker registers and activates", async ({ page }) => {
    await loginAsRep(page);

    const swState = await page.evaluate(async () => {
      const reg = await navigator.serviceWorker.ready;
      return {
        scope: reg.scope,
        active: Boolean(reg.active),
      };
    });

    expect(swState.active).toBeTruthy();
    expect(swState.scope).toContain("/");
  });

  test("offline indicator appears when network is lost", async ({
    page,
    context,
  }) => {
    await loginAsRep(page);
    await page.goto("/app", { waitUntil: "domcontentloaded" });

    // Go offline
    await context.setOffline(true);

    try {
      // The offline status indicator should appear
      await expect(
        page.locator('[data-testid="offline-indicator"], .offline-indicator')
      ).toBeVisible({ timeout: 5_000 });
    } finally {
      await context.setOffline(false);
    }
  });

  test("outbox is exposed on window.jawlaSync", async ({ page }) => {
    await loginAsRep(page);

    const hasSync = await page.evaluate(() => {
      return (
        typeof window.jawlaSync === "object" &&
        typeof window.jawlaSync.enqueue === "function" &&
        typeof window.jawlaSync.hasPending === "function" &&
        typeof window.jawlaSync.flush === "function"
      );
    });

    expect(hasSync).toBeTruthy();
  });

  test("outbox queue starts empty", async ({ page }) => {
    await loginAsRep(page);

    const pending = await page.evaluate(async () => {
      return await window.jawlaSync.hasPending();
    });

    expect(pending).toBeFalsy();
  });

  test("network error during sync leaves items pending", async ({
    page,
    context,
  }) => {
    await loginAsRep(page);

    // Intercept /app/sync to simulate failure
    await page.route("**/app/sync", (route) =>
      route.abort("connectionrefused")
    );

    // Enqueue an operation
    const result = await page.evaluate(async () => {
      return await window.jawlaSync.enqueue("expense", {
        amount: 10,
        category: "fuel",
        note: "offline test",
      });
    });

    expect(result.status).toBe("pending");

    // Try to flush — should fail silently
    await page.evaluate(async () => {
      await window.jawlaSync.flush();
    });

    // Item should still be pending
    const stillPending = await page.evaluate(async () => {
      return await window.jawlaSync.hasPending();
    });

    expect(stillPending).toBeTruthy();

    await page.unroute("**/app/sync");
  });

  test("pending count updates after enqueue", async ({ page }) => {
    await loginAsRep(page);

    // Clear any previous state
    await page.evaluate(async () => {
      await window.jawlaSync
        .discard((await window.jawlaSync.all())[0]?.id)
        .catch(() => {});
    });

    // Enqueue one item
    await page.evaluate(async () => {
      await window.jawlaSync.enqueue("expense", {
        amount: 5,
        category: "transport",
      });
    });

    const status = await page.evaluate(async () => {
      const items = await window.jawlaSync.all();
      return {
        total: items.length,
        pending: items.filter((i) => i.status === "pending").length,
      };
    });

    expect(status.total).toBeGreaterThanOrEqual(1);
    expect(status.pending).toBeGreaterThanOrEqual(1);

    // Cleanup
    await page.evaluate(async () => {
      const items = await window.jawlaSync.all();
      for (const item of items) {
        await window.jawlaSync.discard(item.id);
      }
    });
  });
});
