async (page) => {
  const results = {};

  // Step 0: Clear all cookies and storage
  await page.context().clearCookies();
  await page.evaluate(() => {
    localStorage.clear();
    sessionStorage.clear();
  });

  // Step 1: Login as admin
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

  results.loginSuccess = true;
  results.dashboardUrl = page.url();

  // Capture dashboard
  await page.screenshot({
    path: "screenshots/01-dashboard-success.png",
    scale: "css",
    type: "png",
    fullPage: true,
  });
  results.dashboard = await page.evaluate(() => {
    const m = document.querySelector("main");
    return {
      content: (m ? m.innerText : "").substring(0, 600),
      hasSpinner: (m ? m.innerText : "").includes("Loading..."),
    };
  });

  // Helper: navigate and capture a screen
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

    // Test search empty
    if (data.hasSearch) {
      const s = await page.$('input[type="search"]');
      if (s) {
        await s.fill("zzzznonexistent");
        await page.waitForTimeout(2000);
        data.emptySearch = await page.evaluate(() => {
          const body = document.body.innerText;
          return {
            hasEmptyMsg:
              body.includes("No results") || body.includes("لا يوجد"),
            snippet: (
              document.querySelector("main")?.innerText || ""
            ).substring(0, 300),
          };
        });
        await page.screenshot({
          path: `screenshots/admin-${name}-empty-search.png`,
          scale: "css",
          type: "png",
        });
      }
    }

    return data;
  }

  // Test screens one at a time
  const screens = [
    { name: "customers", url: "/admin/customers" },
    { name: "invoices", url: "/admin/invoices" },
    { name: "payments", url: "/admin/payments" },
    { name: "stocks", url: "/admin/stocks" },
    { name: "users", url: "/admin/users" },
    { name: "routes", url: "/admin/routes" },
    { name: "expenses", url: "/admin/expenses" },
  ];

  for (const screen of screens) {
    try {
      results[screen.name] = await testScreen(screen.name, screen.url);
    } catch (e) {
      results[screen.name] = { error: e.message.substring(0, 200) };
    }
  }

  // Test error state: invoice detail with invalid ID
  try {
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
      statusCode:
        document.title.includes("404") ||
        document.title.includes("Not Found") ||
        document.body.innerText.includes("404")
          ? "404"
          : "other",
    }));
    await page.screenshot({
      path: "screenshots/admin-invoices-error.png",
      scale: "css",
      type: "png",
    });
  } catch (e) {
    results.invoiceError = { error: e.message.substring(0, 200) };
  }

  // Test error state: product detail with invalid ID
  try {
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
  } catch (e) {
    results.productError = { error: e.message.substring(0, 200) };
  }

  return JSON.stringify(results, null, 2);
};
