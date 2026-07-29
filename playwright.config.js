import { defineConfig, devices } from "@playwright/test";

const baseURL = process.env.PLAYWRIGHT_BASE_URL || "http://127.0.0.1:8765";

export default defineConfig({
  testDir: "./tests/JavaScript",
  testMatch: "pwa-readiness.spec.js",
  fullyParallel: false,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [["line"], ["html", { open: "never" }]] : "line",
  use: {
    ...devices["Desktop Chrome"],
    baseURL,
    serviceWorkers: "allow",
    trace: "retain-on-failure",
  },
  webServer: process.env.PLAYWRIGHT_EXTERNAL_SERVER
    ? undefined
    : {
        command: "php artisan serve --host=127.0.0.1 --port=8765",
        url: `${baseURL}/up`,
        reuseExistingServer: false,
        timeout: 120_000,
        env: {
          ...process.env,
          APP_ENV: "testing",
          APP_DEBUG: "false",
          APP_URL: baseURL,
          CACHE_STORE: process.env.CI ? process.env.CACHE_STORE : "array",
          SESSION_DRIVER: process.env.CI ? process.env.SESSION_DRIVER : "file",
          QUEUE_CONNECTION: process.env.CI
            ? process.env.QUEUE_CONNECTION
            : "sync",
          SENTRY_LARAVEL_DSN: "",
        },
      },
});
