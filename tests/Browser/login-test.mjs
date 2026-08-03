async (page) => {
  await page.fill('input[type="password"]', "}xzk8FOOVI1UFi&;57rz4&r");
  await page.click('button[type="submit"]');
  await page.waitForURL("**/app**", { timeout: 10000 });
};
