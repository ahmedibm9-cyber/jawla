@php($ar = app()->getLocale() === 'ar')
<x-filament-panels::page>
    @if(count($this->points) === 0)
        <x-filament::section>
            <p class="text-gray-500">{{ $ar ? 'لا يوجد عملاء لديهم إحداثيات موقع بعد.' : 'No customers have location coordinates yet.' }}</p>
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">
                {{ $ar ? 'مواقع العملاء' : 'Customer locations' }}
                <span class="text-sm text-gray-500">({{ count($this->points) }})</span>
            </x-slot>

            <div
                x-data="customerMap({{ \Illuminate\Support\Js::from($this->points) }})"
                x-init="initMap()"
                wire:ignore
            >
                <div id="customer-map" style="width:100%;height:520px;border-radius:8px;border:1px solid #d1d5db;"></div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
<script>
    window.customerMap = function (points) {
        return {
            points,
            map: null,
            initMap() {
                const tryInit = () => {
                    if (!window.L) { setTimeout(tryInit, 50); return; }
                    L.Icon.Default.imagePath = 'https://unpkg.com/leaflet@1.9.4/dist/images/';
                    this.map = L.map('customer-map', { zoomControl: true });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19,
                    }).addTo(this.map);

                const bounds = [];
                const esc = (s) => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
                this.points.forEach((p) => {
                    const marker = L.marker([p.lat, p.lng]).addTo(this.map);
                    const parts = [`<strong>${esc(p.name)}</strong>`];
                    if (p.code) parts.push(`#${esc(p.code)}`);
                    if (p.route) parts.push(esc(p.route));
                    marker.bindPopup(parts.join('<br>'));
                    bounds.push([p.lat, p.lng]);
                });

                if (bounds.length === 1) {
                    this.map.setView(bounds[0], 14);
                } else if (bounds.length > 1) {
                    this.map.fitBounds(bounds, { padding: [40, 40] });
                }
                };
                tryInit();
            },
        };
    };
</script>
@endpush
