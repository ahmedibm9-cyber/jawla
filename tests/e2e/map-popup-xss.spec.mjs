import { expect, test } from "@playwright/test";
import path from "node:path";
import { fileURLToPath } from "node:url";

const projectRoot = path.resolve(
  path.dirname(fileURLToPath(import.meta.url)),
  "../.."
);
const popupScript = path.join(
  projectRoot,
  "resources/js/maps/popup-content.js"
);

const payloads = [
  '<img src=x onerror="window.__jawlaXss = true">',
  '<svg onload="window.__jawlaXss = true"></svg>',
  "</strong><script>window.__jawlaXss = true</script>",
  '"><a href="javascript:window.__jawlaXss=true">click</a>',
];

for (const kind of ["rep", "customer"]) {
  test(`${kind} popup renders stored payloads as inert text`, async ({
    page,
  }) => {
    await page.setContent("<main id='target'></main>");
    await page.addScriptTag({ path: popupScript });

    for (const payload of payloads) {
      const result = await page.evaluate(
        ({ kind, payload }) => {
          window.__jawlaXss = false;
          const content = window.JawlaMapPopups[kind]({
            name: payload,
            code: payload,
            route: payload,
            seen_at: payload,
            minutes_ago: 1,
            accuracy: 5,
          });
          document.querySelector("#target").replaceChildren(content);

          return {
            executed: window.__jawlaXss,
            text: content.textContent,
            activeElements: content.querySelectorAll(
              "img,svg,script,a,iframe,object"
            ).length,
          };
        },
        { kind, payload }
      );

      expect(result.executed).toBe(false);
      expect(result.activeElements).toBe(0);
      expect(result.text).toContain(payload);
    }
  });
}
