@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'latitudeField' => 'latitude',
    'longitudeField' => 'longitude',
    'defaultLatitude' => 24.7136,
    'defaultLongitude' => 46.6753,
    'defaultZoom' => 15,
    'label' => null,
])

@php
    $id = $id ?? 'leaflet-map-' . uniqid();
    $latitudeId = $latitudeField . '_' . $id;
    $longitudeId = $longitudeField . '_' . $id;
@endphp

<div x-data="leafletMapPicker('{{ $latitudeField }}', '{{ $longitudeField }}', {{ $defaultLatitude }}, {{ $defaultLongitude }}, {{ $defaultZoom }})"
     x-init="initMap()"
     class="w-full">
    
    <div id="{{ $id }}" style="width: 100%; height: 300px; border-radius: 8px; border: 1px solid #d1d5db;"></div>

    <div class="mt-2 grid grid-cols-2 gap-2">
        <input
            type="hidden"
            wire:model.live="{{ $latitudeField }}"
            id="{{ $latitudeId }}"
            name="{{ $latitudeField }}"
            value="{{ old($latitudeField, $get($latitudeField) ?? $defaultLatitude) }}"
        >
        <input
            type="hidden"
            wire:model.live="{{ $longitudeField }}"
            id="{{ $longitudeId }}"
            name="{{ $longitudeField }}"
            value="{{ old($longitudeField, $get($longitudeField) ?? $defaultLongitude) }}"
        >
    </div>

    @if ($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>
    @endif
</div>

@push('scripts')
<script>
    window.leafletMapPicker = function(latitudeField, longitudeField, defaultLat, defaultLng, defaultZoom) {
        return {
            latitudeField,
            longitudeField,
            defaultLat,
            defaultLng,
            defaultZoom,
            map: null,
            marker: null,
            online: navigator.onLine,
            
            initMap() {
                import('leaflet').then(mod => {
                    window.L = mod.default;

                    // Get initial values from hidden inputs
                    const latInput = document.getElementById(this.latitudeField + '_' + this.$id);
                    const lngInput = document.getElementById(this.longitudeField + '_' + this.$id);
                    
                    let initialLat = parseFloat(latInput?.value) || this.defaultLat;
                    let initialLng = parseFloat(lngInput?.value) || this.defaultLng;

                    // Initialize map
                    this.map = L.map(this.$el.querySelector('[id^="leaflet-map-"]'), {
                        center: [initialLat, initialLng],
                        zoom: this.defaultZoom,
                        zoomControl: true,
                    });

                    // Add OpenStreetMap tiles
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19,
                }).addTo(this.map);

                // Add marker
                this.marker = L.marker([initialLat, initialLng], {
                    draggable: true,
                }).addTo(this.map);

                // Update hidden inputs when marker is dragged
                this.marker.on('dragend', (e) => {
                    const position = e.target.getLatLng();
                    this.updateCoordinates(position.lat, position.lng);
                });

                // Click on map to move marker
                this.map.on('click', (e) => {
                    this.marker.setLatLng(e.latlng);
                    this.updateCoordinates(e.latlng.lat, e.latlng.lng);
                });

                // Handle online/offline
                window.addEventListener('online', () => this.online = true);
                window.addEventListener('offline', () => this.online = false);

                // Locate user on init
                this.locateUser();
                }).catch(err => console.error('Leaflet load failed:', err));
            },

            locateUser() {
                if (!navigator.geolocation) {
                    console.warn('Geolocation is not supported');
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        this.updateCoordinates(lat, lng);
                        this.map.setView([lat, lng], this.defaultZoom);
                    },
                    (err) => {
                        console.warn('Geolocation error:', err.message);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            },

            updateCoordinates(lat, lng) {
                // Update hidden inputs
                const latInput = document.getElementById(this.latitudeField + '_' + this.$id);
                const lngInput = document.getElementById(this.longitudeField + '_' + this.$id);
                
                if (latInput) latInput.value = lat.toFixed(6);
                if (lngInput) lngInput.value = lng.toFixed(6);
                
                // Trigger Livewire update
                if (latInput) latInput.dispatchEvent(new Event('input', { bubbles: true }));
                if (lngInput) lngInput.dispatchEvent(new Event('input', { bubbles: true }));

                // Update marker position
                if (this.marker) {
                    this.marker.setLatLng([lat, lng]);
                }
                
                // Center map
                if (this.map) {
                    this.map.setView([lat, lng], this.map.getZoom());
                }
            },

            // Cleanup on destroy
            destroy() {
                if (this.map) {
                    this.map.remove();
                    this.map = null;
                }
                if (this.marker) {
                    this.marker = null;
                }
            }
        }
    }
</script>
@endpush