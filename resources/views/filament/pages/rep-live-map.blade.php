@php($ar = app()->getLocale() === 'ar')
<x-filament-panels::page>
    <div wire:poll.30s="broadcastPoints" style="display:none;"></div>

    <div
        wire:ignore
        x-data="repLiveMap({{ \Illuminate\Support\Js::from($this->points) }})"
        x-on:pings-updated.window="refresh($event.detail.points)"
        class="relative"
    >
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                {{ $ar ? 'آخر ظهور خلال 30 دقيقة. يتحدث تلقائيًا كل 30 ثانية.' : 'Last seen within 30 min. Auto-refreshes every 30s.' }}
                — {{ count($this->points) }} {{ $ar ? 'على الوردية' : 'on shift' }}
            </p>
        </div>

        <div
            id="rep-live-map"
            role="img"
            aria-label="{{ $ar ? 'خريطة مواقع المندوبين المباشرة' : 'Live rep locations map' }}"
            class="h-[calc(100vh-12rem)] w-full rounded-lg border border-gray-200 dark:border-gray-700"
        ></div>

        @if(count($this->points) === 0)
            <p class="absolute left-1/2 top-1/2 z-[1000] -translate-x-1/2 -translate-y-1/2 rounded-lg bg-white/90 px-4 py-2 text-sm text-gray-500 shadow dark:bg-gray-900/90">
                {{ $ar ? 'لا يوجد مندوبون على الوردية حاليًا.' : 'No reps are on shift right now.' }}
            </p>
        @endif
    </div>
</x-filament-panels::page>

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<script>
    window.repLiveMap = function (points) {
        return {
            points,
            map: null,
            layer: null,
            init() {
                const tryInit = () => {
                    if (!window.L) { setTimeout(tryInit, 50); return; }
                    const el = document.getElementById('rep-live-map');
                    if (!el || el.offsetHeight === 0) { setTimeout(tryInit, 100); return; }

                    L.Icon.Default.imagePath = '/images/';
                    this.map = L.map('rep-live-map', { zoomControl: true });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        maxZoom: 19,
                    }).addTo(this.map);
                    this.layer = L.layerGroup().addTo(this.map);

                    this.map.setView([24.7136, 46.6753], 6);
                    this.draw(this.points, true);

                    // Fix tile alignment after layout settles
                    setTimeout(() => this.map.invalidateSize(), 200);
                };
                tryInit();
            },
            refresh(points) {
                this.points = points ?? [];
                this.draw(this.points, false);
            },
            draw(points, fit) {
                if (!this.layer) return;
                this.layer.clearLayers();
                const bounds = [];
                points.forEach((p) => {
                    const marker = L.marker([p.lat, p.lng]);
                    const content = JawlaMapPopups.rep(p);
                    marker.bindPopup(content);
                    marker.addTo(this.layer);
                    bounds.push([p.lat, p.lng]);
                });
                if (fit && bounds.length === 1) {
                    this.map.setView(bounds[0], 14);
                } else if (fit && bounds.length > 1) {
                    this.map.fitBounds(bounds, { padding: [40, 40] });
                }
                if (bounds.length) this.map.invalidateSize();
            },
        };
    };
</script>
@endpush
