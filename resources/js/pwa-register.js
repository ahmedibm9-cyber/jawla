import { hasStaleRecords } from "./offline/outbox.js";

if ("serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    navigator.serviceWorker
      .register("/sw.js")
      .then((registration) => {
        let updatePromptDismissed = false;
        let updatePromptActive = false;
        const promptForUpdate = async () => {
          if (
            !registration.waiting ||
            updatePromptDismissed ||
            updatePromptActive
          )
            return;
          if (await window.jawlaSync?.hasPending?.()) {
            return;
          }
          if (await hasStaleRecords()) {
            window.alert(
              document.documentElement.lang === "ar"
                ? "يرجى مزامنة بياناتك غير المتصلة قبل التحديث."
                : "Please sync your offline data before updating."
            );
            return;
          }
          updatePromptActive = true;
          try {
            if (
              window.confirm(
                document.documentElement.lang === "ar"
                  ? "يتوفر تحديث آمن. أعد التحميل للتحديث؟"
                  : "A safe update is ready. Reload to update?"
              )
            ) {
              registration.waiting.postMessage({ type: "ACTIVATE_UPDATE" });
            } else {
              updatePromptDismissed = true;
            }
          } finally {
            updatePromptActive = false;
          }
        };

        if (registration.waiting) promptForUpdate();
        registration.addEventListener("updatefound", () => {
          const worker = registration.installing;
          worker?.addEventListener("statechange", () => {
            if (
              worker.state === "installed" &&
              navigator.serviceWorker.controller
            )
              promptForUpdate();
          });
        });
        navigator.serviceWorker.addEventListener("controllerchange", () =>
          window.location.reload()
        );
        window.addEventListener("jawla-sync-status", () => promptForUpdate());

        // Register periodic background sync if supported
        if ("periodicSync" in registration) {
          navigator.permissions
            .query({ name: "periodic-background-sync" })
            .then((status) => {
              if (status.state === "granted") {
                registration.periodicSync
                  .register("jawla-periodic-sync", {
                    minInterval: 30 * 60 * 1000, // 30 minutes
                  })
                  .catch(() => {});
              }
            })
            .catch(() => {});
        }
      })
      .catch(() => {
        /* ignore in dev */
      });
  });
}
