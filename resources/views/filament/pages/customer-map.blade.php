@php($ar = app()->getLocale() === 'ar')
<x-filament-panels::page>
    @if(count($this->points) === 0)
        <div class="py-8 text-center text-sm text-gray-500">
            {{ $ar ? 'لا يوجد عملاء لديهم إحداثيات موقع بعد.' : 'No customers have location coordinates yet.' }}
        </div>
    @else
        <div
            x-data="customerMap({{ \Illuminate\Support\Js::from($this->points) }})"
            x-init="initMap()"
            wire:ignore
        >
            <p class="mb-3 text-sm text-gray-500">
                {{ $ar ? 'إجمالي' : 'Total' }}: {{ count($this->points) }} {{ $ar ? 'عميل' : 'customers' }}
            </p>

            <div
                id="customer-map"
                role="img"
                aria-label="{{ $ar ? 'خريطة مواقع العملاء' : 'Customer locations map' }}"
                style="width:100%;height:calc(100vh - 12rem);border-radius:8px;border:1px solid #d1d5db;"
            ></div>
        </div>
    @endif
</x-filament-panels::page>

@push('scripts')
<link rel="stylesheet" href="/leaflet.css" />
<script src="/leaflet.js"></script>
<script src="/popup-content.js"></script>
<script>
    window.customerMap = function (points) {
        return {
            points,
            map: null,
            initMap() {
                const tryInit = () => {
                    if (!window.L) { setTimeout(tryInit, 50); return; }
                    const el = document.getElementById('customer-map');
                    if (!el || el.offsetHeight === 0) { setTimeout(tryInit, 100); return; }

                    L.Icon.Default.imagePath = '/images/';
                    this.map = L.map('customer-map', { zoomControl: true });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
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

                    setTimeout(() => this.map.invalidateSize(), 200);
                };
                tryInit();
            },
        };
    };
</script>
@endpush
