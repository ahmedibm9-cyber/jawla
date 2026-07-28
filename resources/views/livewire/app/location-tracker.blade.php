<div>
    {{-- On-shift GPS disclosure notice (PR-015). Visible while the rep is on-shift. --}}
    @if ($showNotice)
        <div
            role="status"
            aria-live="polite"
            style="position:fixed;bottom:4.5rem;left:50%;transform:translateX(-50%);z-index:9998;max-width:90vw;padding:.375rem .75rem;border-radius:.5rem;background:rgba(15,23,42,.85);color:#f1f5f9;font-size:.75rem;text-align:center;backdrop-filter:blur(4px)"
        >
            📍 {{ __('app.on_shift_location_tracked') }}
        </div>
    @endif

    {{-- On-shift location beacon. Hidden; posts geolocation every 60s (low
         accuracy / cached fixes to spare battery). Server drops off-shift pings. --}}
    <div
        wire:ignore
        style="display:none;"
        x-data="{
            timer: null,
            ping() {
                if (!('geolocation' in navigator)) return;
                navigator.geolocation.getCurrentPosition(
                    (p) => $wire.recordPing(p.coords.latitude, p.coords.longitude, p.coords.accuracy),
                    () => {},
                    { enableHighAccuracy: false, maximumAge: 60000, timeout: 15000 }
                );
            },
            init() {
                this.ping();
                this.timer = setInterval(() => this.ping(), 60000);
            },
            destroy() {
                if (this.timer) clearInterval(this.timer);
            },
        }"
    ></div>
</div>
