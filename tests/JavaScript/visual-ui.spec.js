import { expect, test } from "@playwright/test";

const viewports = [
  { name: "mobile-small", width: 320, height: 568 },
  { name: "mobile", width: 390, height: 844 },
  { name: "tablet", width: 768, height: 1024 },
  { name: "laptop-14", width: 1366, height: 768 },
  { name: "desktop", width: 1920, height: 1080 },
];

const locales = [
  { code: "ar", direction: "rtl" },
  { code: "en", direction: "ltr" },
];

async function expectNoPageOverflow(page) {
  const dimensions = await page.evaluate(() => ({
    clientWidth: document.documentElement.clientWidth,
    scrollWidth: document.documentElement.scrollWidth,
  }));

  expect(
    dimensions.scrollWidth,
    `page width ${dimensions.scrollWidth}px exceeds viewport ${dimensions.clientWidth}px`
  ).toBeLessThanOrEqual(dimensions.clientWidth);
}

async function expectRenderedControlsToBeNamed(page) {
  const unnamedControls = await page
    .locator(
      'button:visible, a[href]:visible, input:not([type="hidden"]):visible, select:visible, textarea:visible, [role="button"]:visible, [role="link"]:visible, [role="combobox"]:visible'
    )
    .evaluateAll((controls) =>
      controls
        .filter((control) => {
          const labelledBy = control.getAttribute("aria-labelledby");
          const labelledByText = labelledBy
            ? labelledBy
                .split(/\s+/)
                .map((id) => document.getElementById(id)?.textContent || "")
                .join(" ")
                .trim()
            : "";
          const associatedLabel =
            control.id &&
            document
              .querySelector(`label[for="${CSS.escape(control.id)}"]`)
              ?.textContent?.trim();
          const wrappedLabel = control.closest("label")?.textContent?.trim();
          const accessibleText = [
            control.getAttribute("aria-label"),
            labelledByText,
            associatedLabel,
            wrappedLabel,
            control.getAttribute("alt"),
            control.getAttribute("title"),
            control.textContent,
          ]
            .filter(Boolean)
            .join(" ")
            .trim();

          return accessibleText.length === 0;
        })
        .map((control) => ({
          tag: control.tagName.toLowerCase(),
          id: control.id,
          type: control.getAttribute("type"),
          role: control.getAttribute("role"),
          html: control.outerHTML.slice(0, 300),
        }))
    );

  expect(
    unnamedControls,
    "every rendered interactive control must be named"
  ).toEqual([]);
}

test.describe("public visual UI matrix", () => {
  for (const locale of locales) {
    for (const viewport of viewports) {
      test(`${locale.code} login is usable at ${viewport.name}`, async ({
        page,
      }, testInfo) => {
        await page.setViewportSize({
          width: viewport.width,
          height: viewport.height,
        });

        const response = await page.goto(`/locale/${locale.code}`, {
          waitUntil: "networkidle",
        });

        expect(response?.ok()).toBeTruthy();
        await expect(page.locator("html")).toHaveAttribute("lang", locale.code);
        await expect(page.locator("html")).toHaveAttribute(
          "dir",
          locale.direction
        );
        await expect(
          page.getByRole("textbox", {
            name: locale.code === "ar" ? /البريد الإلكتروني/ : /email address/i,
          })
        ).toBeVisible();
        await expect(
          page.getByRole("textbox", {
            name: locale.code === "ar" ? /كلمة المرور/ : /password/i,
          })
        ).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();

        await expectNoPageOverflow(page);
        await expectRenderedControlsToBeNamed(page);

        await page.screenshot({
          path: testInfo.outputPath(
            `login-${locale.code}-${viewport.name}.png`
          ),
          fullPage: true,
          animations: "disabled",
        });
      });
    }
  }

  test("login has a visible and logical keyboard focus path", async ({
    page,
  }) => {
    await page.goto("/locale/en", { waitUntil: "networkidle" });

    const email = page.getByRole("textbox", { name: /email address/i });
    const password = page.getByRole("textbox", { name: /password/i });
    const submit = page.getByRole("button", { name: /^sign in$/i });

    await email.focus();
    await expect(email).toBeFocused();
    await password.focus();
    await expect(password).toBeFocused();
    await submit.focus();
    await expect(submit).toBeFocused();
  });

  test("validation, offline, not-found, and expired-page states are branded and actionable", async ({
    page,
  }, testInfo) => {
    await page.goto("/locale/en", { waitUntil: "networkidle" });

    const formIsInvalid = await page
      .locator("form")
      .evaluate((form) => !form.checkValidity());
    expect(formIsInvalid).toBeTruthy();
    await expect(
      page.getByRole("textbox", { name: /email address/i })
    ).toHaveAttribute("required");
    await expect(
      page.getByRole("textbox", { name: /password/i })
    ).toHaveAttribute("required");
    await expectRenderedControlsToBeNamed(page);

    for (const state of [
      { name: "offline", path: "/offline", status: 200 },
      {
        name: "not-found",
        path: "/visual-test-route-does-not-exist",
        status: 404,
      },
    ]) {
      const response = await page.goto(state.path, {
        waitUntil: "domcontentloaded",
      });
      expect(response?.status()).toBe(state.status);
      await expect(page.locator("main")).toBeVisible();
      await expectNoPageOverflow(page);
      await expectRenderedControlsToBeNamed(page);
      await page.screenshot({
        path: testInfo.outputPath(`${state.name}.png`),
        fullPage: true,
        animations: "disabled",
      });
    }
  });
});
