@php($ar = app()->getLocale() === 'ar')
<x-filament-panels::page>
    {{-- Poll every 30s; broadcastPoints dispatches fresh points to the map. --}}
    <div wire:poll.30s="broadcastPoints" style="display:none;"></div>

    <x-filament::section>
        <x-slot name="heading">
            {{ $ar ? 'مواقع المندوبين المباشرة' : 'Live rep locations' }}
            <span class="text-sm text-gray-500">({{ count($this->points) }} {{ $ar ? 'على الوردية' : 'on shift' }})</span>
        </x-slot>
        <x-slot name="description">
            {{ $ar ? 'آخر ظهور خلال 30 دقيقة. يتحدث تلقائيًا كل 30 ثانية.' : 'Last seen within 30 min. Auto-refreshes every 30s.' }}
        </x-slot>

        <div
            wire:ignore
            x-data="repLiveMap({{ \Illuminate\Support\Js::from($this->points) }})"
            x-on:pings-updated.window="refresh($event.detail.points)"
        >
            <div id="rep-live-map" style="width:100%;height:520px;border-radius:8px;border:1px solid #d1d5db;"></div>
        </div>

        @if(count($this->points) === 0)
            <p class="mt-3 text-sm text-gray-500">{{ $ar ? 'لا يوجد مندوبون على الوردية حاليًا.' : 'No reps are on shift right now.' }}</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha384-cxOPjt7s7azlZ7s3I8/US2q3BkQ1cL0GqO3B7i0WCGv+M2T6U54E4c5T5Vq46s5" crossorigin="anonymous"></script>
<script>
    window.repLiveMap = function (points) {
        return {
            points,
            map: null,
            layer: null,
            init() {
                setTimeout(() => {
                    this.map = L.map('rep-live-map', { zoomControl: true });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19,
                    }).addTo(this.map);
                    this.layer = L.layerGroup().addTo(this.map);

                    // Default view (Riyadh) until we have fixes.
                    this.map.setView([24.7136, 46.6753], 6);
                    this.draw(this.points, true);
                }, 100);
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
                    const parts = [`<strong>${p.name ?? ''}</strong>`, `${p.seen_at} (${p.minutes_ago}m)`];
                    if (p.accuracy) parts.push(`±${Math.round(p.accuracy)}m`);
                    marker.bindPopup(parts.join('<br>'));
                    marker.addTo(this.layer);
                    bounds.push([p.lat, p.lng]);
                });
                if (fit && bounds.length === 1) {
                    this.map.setView(bounds[0], 14);
                } else if (fit && bounds.length > 1) {
                    this.map.fitBounds(bounds, { padding: [40, 40] });
                }
            },
        };
    };
</script>
@endpush
