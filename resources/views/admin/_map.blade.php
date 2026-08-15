<section class="map-card">
    <div class="map-card-header"><div><span class="panel-kicker">{{ $title ?? 'Localisation' }}</span><h2>{{ $address?->city ?? 'Adresse' }}</h2></div><span class="map-pin">●</span></div>
    @if ($address?->latitude && $address?->longitude)
        <div id="address-map" class="address-map"></div>
        <div class="map-card-footer"><span>{{ $address->latitude }}, {{ $address->longitude }}</span><a href="https://www.openstreetmap.org/?mlat={{ $address->latitude }}&mlon={{ $address->longitude }}#map=17/{{ $address->latitude }}/{{ $address->longitude }}" target="_blank" rel="noopener">Ouvrir dans OpenStreetMap ↗</a></div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            const addressMap = L.map('address-map', { zoomControl: false, scrollWheelZoom: false }).setView([{{ $address->latitude }}, {{ $address->longitude }}], 16);
            L.control.zoom({ position: 'bottomright' }).addTo(addressMap);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(addressMap);
            L.circleMarker([{{ $address->latitude }}, {{ $address->longitude }}], { radius: 9, color: '#ffffff', weight: 3, fillColor: '#0f766e', fillOpacity: 1 }).addTo(addressMap);
            addressMap.whenReady(() => window.setTimeout(() => addressMap.invalidateSize(), 0));
        </script>
    @else
        <div class="map-empty"><span>⌖</span><p>Les coordonnées de cette adresse ne sont pas encore disponibles.</p></div>
    @endif
</section>
