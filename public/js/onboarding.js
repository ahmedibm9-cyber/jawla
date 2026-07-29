/**
 * JAWLA Onboarding Tour
 * Dependency-free, bilingual AR/EN, RTL-aware, and keyboard accessible.
 */

(function () {
  "use strict";

  const isRTL = document.documentElement.dir === "rtl";
  const lang = document.documentElement.lang || "en";
  const t = (key) => window.__onboarding?.[key] || key;

  class JawlaTour {
    constructor(steps) {
      this.steps = steps;
      this.currentStep = null;
      this.currentIndex = -1;
      this.listeners = new Map();
      this.keyHandler = (event) => {
        if (event.key === "Tab") {
          this.trapFocus(event);
          return;
        }

        if (event.key === "Escape") {
          event.preventDefault();
          this.cancel();
        }
        if (event.key === "ArrowRight") {
          event.preventDefault();
          isRTL ? this.back() : this.next();
        }
        if (event.key === "ArrowLeft") {
          event.preventDefault();
          isRTL ? this.next() : this.back();
        }
      };
      this.resizeHandler = () => this.position();
    }

    on(event, callback) {
      const callbacks = this.listeners.get(event) || [];
      callbacks.push(callback);
      this.listeners.set(event, callbacks);
    }

    emit(event) {
      (this.listeners.get(event) || []).forEach((callback) => callback());
    }

    start() {
      if (this.steps.length === 0) return;
      this.currentIndex = 0;
      this.previouslyFocused = document.activeElement;
      document.addEventListener("keydown", this.keyHandler);
      window.addEventListener("resize", this.resizeHandler);
      window.addEventListener("scroll", this.resizeHandler, true);
      this.show();
    }

    next() {
      if (this.currentIndex >= this.steps.length - 1) {
        this.finish("complete");
        return;
      }

      this.hideCurrent();
      this.currentIndex += 1;
      this.show();
    }

    back() {
      if (this.currentIndex <= 0) return;

      this.hideCurrent();
      this.currentIndex -= 1;
      this.show();
    }

    cancel() {
      this.finish("cancel");
    }

    finish(event) {
      this.hideCurrent();
      document.removeEventListener("keydown", this.keyHandler);
      window.removeEventListener("resize", this.resizeHandler);
      window.removeEventListener("scroll", this.resizeHandler, true);
      this.previouslyFocused?.focus?.();
      this.emit(event);
    }

    hideCurrent() {
      this.currentStep?.when?.hide?.();
      this.target?.classList.remove("shepherd-target");
      this.overlay?.remove();
      this.dialog?.remove();
      this.target = null;
      this.overlay = null;
      this.dialog = null;
    }

    show() {
      const step = this.steps[this.currentIndex];
      this.currentStep = step;
      this.target = step.attachTo?.element
        ? document.querySelector(step.attachTo.element)
        : null;

      this.target?.classList.add("shepherd-target");
      this.target?.scrollIntoView({ behavior: "smooth", block: "center" });

      this.overlay = document.createElement("div");
      this.overlay.className = "jawla-tour-overlay";
      this.overlay.addEventListener("click", () => this.cancel());

      this.dialog = document.createElement("section");
      this.dialog.className =
        `shepherd-element shepherd-has-cancel-icon ${step.classes || ""}`.trim();
      this.dialog.setAttribute("role", "dialog");
      this.dialog.setAttribute("aria-modal", "true");

      const titleId = `jawla-tour-title-${step.id}`;
      const textId = `jawla-tour-text-${step.id}`;
      this.dialog.setAttribute("aria-labelledby", titleId);
      this.dialog.setAttribute("aria-describedby", textId);

      const header = document.createElement("header");
      header.className = "shepherd-header";

      const heading = document.createElement("h2");
      heading.className = "shepherd-title";
      heading.id = titleId;
      heading.textContent = step.title;

      const counter = document.createElement("span");
      counter.className = "shepherd-step-counter";
      counter.textContent = `${this.currentIndex + 1} / ${this.steps.length}`;

      const close = document.createElement("button");
      close.className = "shepherd-cancel-icon";
      close.type = "button";
      close.setAttribute("aria-label", lang === "ar" ? "إغلاق" : "Close");
      close.textContent = "×";
      close.addEventListener("click", () => this.cancel());

      const text = document.createElement("div");
      text.className = "shepherd-text";
      text.id = textId;
      text.textContent = step.text;

      const footer = document.createElement("footer");
      footer.className = "shepherd-footer";

      step.buttons.forEach((button) => {
        const element = document.createElement("button");
        element.type = "button";
        element.className = `shepherd-button ${button.classes || ""}`.trim();
        element.textContent = button.text;
        element.addEventListener("click", () => {
          if (button.action === "back") this.back();
          else this.next();
        });
        footer.appendChild(element);
      });

      header.append(heading, counter, close);
      this.dialog.append(header, text, footer);
      document.body.append(this.overlay, this.dialog);
      step.when?.show?.();
      this.position();
      this.dialog.querySelector("button")?.focus();
    }

    trapFocus(event) {
      const focusable = this.dialog?.querySelectorAll(
        'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
      );

      if (!focusable?.length) return;

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    position() {
      if (!this.dialog) return;

      const margin = 16;
      const gap = 12;
      const dialogRect = this.dialog.getBoundingClientRect();

      if (!this.target) {
        this.dialog.style.left = `${Math.max(margin, (window.innerWidth - dialogRect.width) / 2)}px`;
        this.dialog.style.top = `${Math.max(margin, (window.innerHeight - dialogRect.height) / 2)}px`;
        return;
      }

      const targetRect = this.target.getBoundingClientRect();
      const placement = this.currentStep.attachTo?.on || "bottom";
      let left = targetRect.left + (targetRect.width - dialogRect.width) / 2;
      let top = targetRect.bottom + gap;

      if (placement === "top") top = targetRect.top - dialogRect.height - gap;
      if (placement === "start") {
        left = isRTL
          ? targetRect.right + gap
          : targetRect.left - dialogRect.width - gap;
        top = targetRect.top + (targetRect.height - dialogRect.height) / 2;
      }
      if (placement === "end") {
        left = isRTL
          ? targetRect.left - dialogRect.width - gap
          : targetRect.right + gap;
        top = targetRect.top + (targetRect.height - dialogRect.height) / 2;
      }

      this.dialog.style.left = `${Math.min(
        Math.max(margin, left),
        window.innerWidth - dialogRect.width - margin,
      )}px`;
      this.dialog.style.top = `${Math.min(
        Math.max(margin, top),
        window.innerHeight - dialogRect.height - margin,
      )}px`;
    }
  }

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
    const tour = new JawlaTour(steps);

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
