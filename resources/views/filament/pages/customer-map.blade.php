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
                <div id="customer-map" role="img" aria-label="{{ $ar ? 'خريطة مواقع العملاء' : 'Customer locations map' }}" style="width:100%;height:520px;border-radius:8px;border:1px solid #d1d5db;"></div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha384-cxOPjt7s7Iz04uaHJceBmS+qpjv2JkIHNVcuOrM+YHwZOmJGBXI00mdUXEq65HTH" crossorigin="anonymous"></script>
<script>
    window.customerMap = function (points) {
        return {
            points,
            map: null,
            initMap() {
                const tryInit = () => {
                    if (!window.L) { setTimeout(tryInit, 50); return; }
                    L.Icon.Default.imagePath = '/images/';
                    this.map = L.map('customer-map', { zoomControl: true });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19,
                    }).addTo(this.map);

                const bounds = [];
                this.points.forEach((p) => {
                    const marker = L.marker([p.lat, p.lng]).addTo(this.map);
                    const content = JawlaMapPopups.customer(p);
                    marker.bindPopup(content);
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
