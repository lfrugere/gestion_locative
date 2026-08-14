@if ($address?->latitude && $address?->longitude)
    <div class="card"><h2>Localisation</h2><div id="address-map" class="address-map"></div></div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIINfQ3e7fK8xJ7xqKp4wXlQjQ5J8T5v1bA=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const addressMap = L.map('address-map').setView([{{ $address->latitude }}, {{ $address->longitude }}], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(addressMap);
        L.marker([{{ $address->latitude }}, {{ $address->longitude }}]).addTo(addressMap);
    </script>
@else
    <div class="card"><h2>Localisation</h2><p class="muted">Les coordonnées de cette adresse ne sont pas encore disponibles.</p></div>
@endif
