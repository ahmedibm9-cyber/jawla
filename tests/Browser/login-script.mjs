async (page) => {
  const pw = "}&xzk8FOOVI1UFi&;57rz4&r";
  await page.fill("input[type=password]", pw);
  await page.click('button:has-text("تسجيل الدخول")');
  await page.waitForTimeout(5000);
};
