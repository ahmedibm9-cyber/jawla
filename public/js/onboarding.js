/**
 * JAWLA Onboarding Tour — Shepherd.js definitions
 * Rep PWA + Admin Panel, bilingual AR/EN, RTL-aware.
 */

(function () {
  "use strict";

  const isRTL = document.documentElement.dir === "rtl";
  const lang = document.documentElement.lang || "en";
  const t = (key) => window.__onboarding?.[key] || key;

  const COMMON = {
    shepherdId: "jawla-onboarding",
    useModalOverlay: true,
    confirmCancel: false,
    keyboardNavigation: true,
    exitOnEsc: true,
    defaultStepOptions: {
      scrollTo: { behavior: "smooth", block: "center" },
      cancelIcon: { enabled: true },
      floatingUIOptions: {
        middleware: [
          { name: "offset", options: { mainAxis: 12, crossAxis: 0 } },
        ],
      },
    },
  };

  /* ------------------------------------------------------------------ */
  /*  Rep PWA steps                                                     */
  /* ------------------------------------------------------------------ */
  const REP_STEPS = [
    {
      id: "welcome",
      title: t("tour_welcome"),
      text: t("tour_welcome_desc"),
      attachTo: { element: ".tab-bar-brand", on: "top" },
      classes: "shepherd-welcome",
      buttons: [
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "todays-plan",
      title: t("tour_todays_plan"),
      text: t("tour_todays_plan_desc"),
      attachTo: { element: ".home-stats", on: "bottom" },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "visits",
      title: t("tour_visits"),
      text: t("tour_visits_desc"),
      attachTo: { element: ".tab-item[href='/app/visits']", on: "top" },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "tab-bar",
      title: t("tour_tab_bar"),
      text: t("tour_tab_bar_desc"),
      attachTo: { element: ".tab-bar", on: "top" },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "quotations",
      title: t("tour_quotations"),
      text: t("tour_quotations_desc"),
      attachTo: { element: ".tab-item[href='/app/quotations']", on: "top" },
      advanceOn: { selector: ".tab-item[href='/app/quotations']", event: "click" },
      when: {
        show() {
          const el = document.querySelector(".tab-item[href='/app/quotations']");
          if (el) el.classList.add("shepherd-highlight-pulse");
        },
        hide() {
          document.querySelectorAll(".shepherd-highlight-pulse").forEach((el) =>
            el.classList.remove("shepherd-highlight-pulse")
          );
        },
      },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "more-menu",
      title: t("tour_more_menu"),
      text: t("tour_more_menu_desc"),
      attachTo: { element: ".tab-item[href='/app/more']", on: "top" },
      advanceOn: { selector: ".tab-item[href='/app/more']", event: "click" },
      when: {
        show() {
          const el = document.querySelector(".tab-item[href='/app/more']");
          if (el) el.classList.add("shepherd-highlight-pulse");
        },
        hide() {
          document.querySelectorAll(".shepherd-highlight-pulse").forEach((el) =>
            el.classList.remove("shepherd-highlight-pulse")
          );
        },
      },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "notifications",
      title: t("tour_notifications"),
      text: t("tour_notifications_desc"),
      attachTo: { element: ".notification-fab[href='/app/notifications']", on: "bottom" },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "offline",
      title: t("tour_offline"),
      text: t("tour_offline_desc"),
      attachTo: { element: ".notification-fab[href='/app/sync-queue']", on: "bottom" },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_finish"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
  ];

  /* ------------------------------------------------------------------ */
  /*  Admin Panel steps                                                 */
  /* ------------------------------------------------------------------ */
  const ADMIN_STEPS = [
    {
      id: "welcome",
      title: t("tour_admin_welcome"),
      text: t("tour_admin_welcome_desc"),
      classes: "shepherd-welcome",
      attachTo: { element: ".fi-sidebar", on: isRTL ? "start" : "end" },
      buttons: [
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "sidebar",
      title: t("tour_admin_sidebar"),
      text: t("tour_admin_sidebar_desc"),
      attachTo: { element: ".fi-sidebar-nav", on: isRTL ? "end" : "start" },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "sales",
      title: t("tour_admin_sales"),
      text: t("tour_admin_sales_desc"),
      attachTo: {
        element: '.fi-sidebar-nav-item:has(a[href*="customers"])',
        on: isRTL ? "end" : "start",
      },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "inventory",
      title: t("tour_admin_inventory"),
      text: t("tour_admin_inventory_desc"),
      attachTo: {
        element: '.fi-sidebar-nav-item:has(a[href*="products"])',
        on: isRTL ? "end" : "start",
      },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "alarms",
      title: t("tour_admin_alarms"),
      text: t("tour_admin_alarms_desc"),
      attachTo: {
        element: '.fi-sidebar-nav-item:has(a[href*="alarms"])',
        on: isRTL ? "end" : "start",
      },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "reports",
      title: t("tour_admin_reports"),
      text: t("tour_admin_reports_desc"),
      attachTo: {
        element: '.fi-sidebar-nav-item:has(a[href*="reports"])',
        on: isRTL ? "end" : "start",
      },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "user-menu",
      title: t("tour_admin_user_menu"),
      text: t("tour_admin_user_menu_desc"),
      attachTo: { element: ".fi-topbar .fi-btn", on: "bottom" },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_next"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
    {
      id: "maps",
      title: t("tour_admin_maps"),
      text: t("tour_admin_maps_desc"),
      attachTo: {
        element: '.fi-sidebar-nav-item:has(a[href*="rep-live-map"])',
        on: isRTL ? "end" : "start",
      },
      buttons: [
        { text: t("tour_back"), action: "back", classes: "shepherd-button-secondary" },
        { text: t("tour_finish"), action: "next", classes: "shepherd-button-primary" },
      ],
    },
  ];

  /* ------------------------------------------------------------------ */
  /*  Tour factory                                                      */
  /* ------------------------------------------------------------------ */
  function createTour(role) {
    const steps = role === "admin" ? ADMIN_STEPS : REP_STEPS;

    const tour = new Shepherd.Tour({
      ...COMMON,
      steps: steps.map((step) => ({
        ...step,
        text: `<p>${step.text}</p><div class="shepherd-step-counter">${
          steps.indexOf(step) + 1
        } / ${steps.length}</div>`,
        when: step.when || {},
      })),
    });

    tour.on("complete", markComplete);
    tour.on("cancel", () => {
      // Only mark complete if user finished all steps
      if (tour.steps.length > 0 && tour.currentStep?.id !== steps[steps.length - 1].id) {
        return;
      }
      markComplete();
    });

    return tour;
  }

  function markComplete() {
    fetch("/api/onboarding/complete", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
        Accept: "application/json",
      },
    }).catch(() => {
      // Silently fail — user still saw the tour
    });
  }

  /* ------------------------------------------------------------------ */
  /*  Public API                                                        */
  /* ------------------------------------------------------------------ */
  window.JawlaOnboarding = {
    start(role) {
      const tour = createTour(role || "rep");
      tour.start();
      return tour;
    },
  };
})();
