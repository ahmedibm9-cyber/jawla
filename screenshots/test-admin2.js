async (page) => {
  const results = {};

  // Clear and login as admin
  await page.context().clearCookies();
  await page.evaluate(() => {
    localStorage.clear();
    sessionStorage.clear();
  });

  await page.goto("https://jawla-production.up.railway.app/admin/login", {
    waitUntil: "domcontentloaded",
    timeout: 15000,
  });
  await page.waitForTimeout(2500);

  if (!page.url().includes("admin/login")) {
    return JSON.stringify({ error: "Not on login: " + page.url() });
  }

  await page
    .getByRole("textbox", { name: "البريد الإلكتروني*" })
    .fill("admin@jawla.test");
  await page.getByRole("textbox", { name: "كلمة المرور*" }).fill("password");
  await page.getByRole("button", { name: "تسجيل الدخول" }).click();
  await page.waitForTimeout(4000);

  if (!page.url().includes("/admin/")) {
    return JSON.stringify({ error: "Login failed: " + page.url() });
  }

  // Test remaining admin screens
  async function testScreen(name, url) {
    await page.goto("https://jawla-production.up.railway.app" + url, {
      waitUntil: "domcontentloaded",
      timeout: 10000,
    });
    await page.waitForTimeout(3000);

    if (!page.url().includes("/admin/")) {
      return { error: "Redirected to " + page.url() };
    }

    const data = await page.evaluate(() => {
      const m = document.querySelector("main");
      const body = m ? m.innerText : "";
      return {
        title: document.title,
        content: body.substring(0, 500),
        hasTable: !!document.querySelector("table"),
        hasSearch: !!document.querySelector('input[type="search"]'),
        isEmpty: body.includes("No results") || body.includes("لا يوجد"),
      };
    });

    await page.screenshot({
      path: `screenshots/admin-${name}-success.png`,
      scale: "css",
      type: "png",
    });

    if (data.hasSearch) {
      const s = await page.$('input[type="search"]');
      if (s) {
        await s.fill("zzzznonexistent");
        await page.waitForTimeout(2000);
        data.emptySearch = await page.evaluate(() => ({
          hasEmptyMsg:
            document.body.innerText.includes("No results") ||
            document.body.innerText.includes("لا يوجد"),
          snippet: (document.querySelector("main")?.innerText || "").substring(
            0,
            300
          ),
        }));
        await page.screenshot({
          path: `screenshots/admin-${name}-empty-search.png`,
          scale: "css",
          type: "png",
        });
      }
    }

    return data;
  }

  results.users = await testScreen("users", "/admin/users");
  results.routes = await testScreen("routes", "/admin/routes");
  results.expenses = await testScreen("expenses", "/admin/expenses");

  // Test error state: invalid invoice
  await page.goto(
    "https://jawla-production.up.railway.app/admin/invoices/99999999",
    { waitUntil: "domcontentloaded", timeout: 10000 }
  );
  await page.waitForTimeout(3000);
  results.invoiceError = await page.evaluate(() => ({
    url: location.href,
    title: document.title,
    content: (
      document.querySelector("main")?.innerText || document.body.innerText
    ).substring(0, 300),
  }));
  await page.screenshot({
    path: "screenshots/admin-invoices-error.png",
    scale: "css",
    type: "png",
  });

  // Test error state: invalid product
  await page.goto(
    "https://jawla-production.up.railway.app/admin/products/99999999/edit",
    { waitUntil: "domcontentloaded", timeout: 10000 }
  );
  await page.waitForTimeout(3000);
  results.productError = await page.evaluate(() => ({
    url: location.href,
    title: document.title,
    content: (
      document.querySelector("main")?.innerText || document.body.innerText
    ).substring(0, 300),
  }));
  await page.screenshot({
    path: "screenshots/admin-products-error.png",
    scale: "css",
    type: "png",
  });

  return JSON.stringify(results, null, 2);
};
