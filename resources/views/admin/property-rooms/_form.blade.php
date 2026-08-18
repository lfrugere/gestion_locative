@if ($errors->any())
    <div class="errors" role="alert"><strong>Veuillez corriger les erreurs suivantes :</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form class="card" method="POST" action="{{ $action }}">
    @csrf
    @if ($room) @method('PUT') @endif
    <div class="form-grid">
        <div class="form-field"><label for="name">Nom de la pièce</label><input id="name" name="name" value="{{ old('name', $room->name ?? '') }}" required autofocus></div>
        <div class="form-field"><label for="surface_m2">Surface (m²)</label><input id="surface_m2" name="surface_m2" type="number" min="0" step="0.01" value="{{ old('surface_m2', $room->surface_m2 ?? '') }}"></div>
        <div class="form-field"><label for="status">Statut</label><select id="status" name="status" required><option value="active" @selected(old('status', $room->status ?? 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $room->status ?? '') === 'inactive')>Inactive</option></select></div>
        <div class="form-field full"><label for="notes">Notes</label><textarea id="notes" name="notes">{{ old('notes', $room->notes ?? '') }}</textarea></div>
    </div>
    <div class="form-actions"><a class="button secondary" href="{{ $room ? route('admin.property-rooms.show', [$property, $room]) : route('admin.properties.show', $property) }}">Annuler</a><button class="button" type="submit">{{ $submitLabel }}</button></div>
</form>
