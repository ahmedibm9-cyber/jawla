<style>
    /* Admin-only refinements. The PWA stylesheet must never leak into Filament. */
    .active-company-chip {
        pointer-events: auto;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.625rem;
        border-radius: 0.5rem;
        background: rgb(255 255 255 / 10%);
        color: #fff;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.4;
        white-space: nowrap;
    }

    .active-company-label {
        max-width: 11.25rem;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .active-company-form {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .active-company-select {
        max-width: 8.75rem;
        border: 1px solid rgb(255 255 255 / 30%);
        border-radius: 0.375rem;
        padding: 0.125rem 0.375rem;
        background: #fff;
        color: #111827;
        font-size: 0.75rem;
    }

    .active-company-btn {
        border: 0;
        border-radius: 0.375rem;
        padding: 0.125rem 0.5rem;
        background: #6db83b;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
    }

    .fi-topbar {
        position: relative;
    }

    .fi-topbar .active-company-chip {
        position: absolute;
        top: 50%;
        left: 50%;
        max-inline-size: calc(100% - 9rem);
        margin-inline-end: 0;
        transform: translate(-50%, -50%);
    }

    .fi-topbar .active-company-label {
        min-width: 0;
    }

    .dashboard-widget-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;
    }

    .dashboard-widget-item {
        position: relative;
        min-width: 0;
        transition: opacity 150ms ease, transform 150ms ease;
    }

    .dashboard-widget-item.is-dragging {
        opacity: 0.45;
        transform: scale(0.98);
        user-select: none;
    }

    .dashboard-widget-drag-handle,
    .dashboard-widget-open-link {
        position: absolute;
        z-index: 10;
        inset-block-start: 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: #fff;
        color: #6b7280;
        box-shadow: 0 1px 2px rgb(0 0 0 / 8%);
        transition: border-color 150ms ease, color 150ms ease, background-color 150ms ease;
    }

    .dashboard-widget-drag-handle {
        inset-inline-end: 0.5rem;
        cursor: grab;
        touch-action: manipulation;
    }

    .dashboard-widget-open-link {
        inset-inline-start: 0.5rem;
        text-decoration: none;
        touch-action: manipulation;
    }

    .dashboard-widget-drag-handle:hover,
    .dashboard-widget-drag-handle:focus-visible,
    .dashboard-widget-open-link:hover,
    .dashboard-widget-open-link:focus-visible {
        border-color: #6db83b;
        color: #4f8c28;
        box-shadow: 0 0 0 2px rgb(109 184 59 / 24%);
    }

    .dashboard-widget-drag-handle:active {
        cursor: grabbing;
    }

    .dashboard-widget-drag-icon,
    .dashboard-widget-open-icon {
        width: 1rem;
        height: 1rem;
    }

    .dashboard-widget-item .fi-wi-stats-overview-stat {
        padding-block-start: 3rem;
    }

    @media (min-width: 768px) {
        .dashboard-widget-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1280px) {
        .dashboard-widget-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 639px) {
        .fi-topbar .active-company-chip {
            max-inline-size: calc(100% - 7.5rem);
            gap: 0.25rem;
            padding-inline: 0.5rem;
        }

        .fi-topbar .active-company-label {
            max-width: 8rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .dashboard-widget-item,
        .dashboard-widget-drag-handle,
        .dashboard-widget-open-link {
            transition: none;
        }
    }
</style>
