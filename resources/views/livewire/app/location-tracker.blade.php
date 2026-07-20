<div>
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
