@php
    $currentType = old('type', $property->type ?? '');
    $isHouse = $currentType === \App\Models\Property::TYPE_HOUSE;
    $isSharedAccommodation = filter_var(old('is_shared_accommodation', $property->is_shared_accommodation ?? false), FILTER_VALIDATE_BOOL);
@endphp
@if ($errors->any())
    <div class="errors" role="alert"><strong>Veuillez corriger les erreurs suivantes :</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form class="card" method="POST" action="{{ $action }}">
    @csrf
    @if ($property) @method('PUT') @endif
    <div class="form-grid">
        <div class="form-field"><label for="reference">Référence</label><input id="reference" name="reference" value="{{ old('reference', $property->reference ?? '') }}" required autofocus></div>
        <div class="form-field"><label for="name">Nom du bien</label><input id="name" name="name" value="{{ old('name', $property->name ?? '') }}" required></div>
        <div class="form-field"><label for="type">Type</label><select id="type" name="type" required>@foreach (\App\Models\Property::TYPE_LABELS as $value => $label)<option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>@endforeach</select><p class="hint">Appartement et parking : rattachement à un immeuble. Maison : adresse propre.</p></div>
        <div class="form-field" id="building-fields"><label for="building_id">Immeuble</label><select id="building_id" name="building_id"><option value="">Aucun</option>@foreach ($buildings as $building)<option value="{{ $building->id }}" @selected((string) old('building_id', $property->building_id ?? '') === (string) $building->id)>{{ $building->name }} ({{ $building->reference }})</option>@endforeach</select></div>
        <div class="form-field"><label for="floor">Étage</label><input id="floor" name="floor" value="{{ old('floor', $property->floor ?? '') }}" placeholder="Rez-de-chaussée, 1, 2…"></div>
        <div class="form-field"><label for="surface_m2">Surface (m²)</label><input id="surface_m2" name="surface_m2" type="number" min="0" step="0.01" value="{{ old('surface_m2', $property->surface_m2 ?? '') }}"></div>
        <div class="form-field" id="shared-accommodation-field"><div class="checkbox-field"><input id="is_shared_accommodation" name="is_shared_accommodation" type="checkbox" value="1" @checked($isSharedAccommodation)><div><label for="is_shared_accommodation">Colocation</label><p class="hint">Disponible uniquement pour les appartements et les maisons.</p></div></div></div>
        <div class="form-field"><label for="status">Statut</label><select id="status" name="status" required><option value="active" @selected(old('status', $property->status ?? 'active') === 'active')>Actif</option><option value="inactive" @selected(old('status', $property->status ?? '') === 'inactive')>Inactif</option></select></div>
        <div class="form-field full" id="address-fields"><h2>Adresse propre à la maison</h2><p class="hint">Ces champs sont requis uniquement pour une maison.</p></div>
        <div class="form-field full" id="address-line1-field"><label for="address_line1">Adresse</label><input id="address_line1" name="address[line1]" value="{{ old('address.line1', $property->address->line1 ?? '') }}"></div>
        <div class="form-field" id="address-line2-field"><label for="address_line2">Complément</label><input id="address_line2" name="address[line2]" value="{{ old('address.line2', $property->address->line2 ?? '') }}"></div>
        <div class="form-field" id="address-postal-field"><label for="address_postal_code">Code postal</label><input id="address_postal_code" name="address[postal_code]" value="{{ old('address.postal_code', $property->address->postal_code ?? '') }}"></div>
        <div class="form-field" id="address-city-field"><label for="address_city">Ville</label><input id="address_city" name="address[city]" value="{{ old('address.city', $property->address->city ?? '') }}"></div>
        <div class="form-field" id="address-country-field"><label for="address_country">Pays</label><input id="address_country" name="address[country]" value="{{ old('address.country', $property->address->country ?? 'FR') }}" maxlength="2"></div>
        <div class="form-field full"><label for="notes">Notes</label><textarea id="notes" name="notes">{{ old('notes', $property->notes ?? '') }}</textarea></div>
    </div>
    <div class="form-actions"><a class="button secondary" href="{{ route('admin.properties.index') }}">Annuler</a><button class="button" type="submit">{{ $submitLabel }}</button></div>
</form>
<script>
    const typeSelect = document.getElementById('type');
    const buildingFields = document.getElementById('building-fields');
    const sharedAccommodationField = document.getElementById('shared-accommodation-field');
    const sharedAccommodationInput = document.getElementById('is_shared_accommodation');
    const addressFields = ['address-fields', 'address-line1-field', 'address-line2-field', 'address-postal-field', 'address-city-field', 'address-country-field'].map((id) => document.getElementById(id));
    const addressRequired = ['address_line1', 'address_postal_code', 'address_city', 'address_country'];
    function updatePropertyFields() {
        const isHouse = typeSelect.value === 'house';
        const isParking = typeSelect.value === 'parking';
        buildingFields.hidden = isHouse;
        buildingFields.setAttribute('aria-hidden', isHouse);
        buildingFields.querySelector('select').disabled = isHouse;
        sharedAccommodationField.hidden = isParking;
        sharedAccommodationField.setAttribute('aria-hidden', isParking);
        sharedAccommodationInput.disabled = isParking;
        if (isParking) sharedAccommodationInput.checked = false;
        addressFields.forEach((field) => { field.hidden = !isHouse; field.setAttribute('aria-hidden', !isHouse); });
        addressRequired.forEach((id) => { document.getElementById(id).required = isHouse; document.getElementById(id).disabled = !isHouse; });
    }
    typeSelect.addEventListener('change', updatePropertyFields);
    updatePropertyFields();
</script>
