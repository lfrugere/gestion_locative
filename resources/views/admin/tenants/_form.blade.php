@if ($errors->any())
    <div class="errors" role="alert"><strong>Veuillez corriger les erreurs suivantes :</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form class="card" method="POST" action="{{ $action }}">
    @csrf
    @if ($tenant) @method('PUT') @endif
    <div class="form-grid">
        <div class="form-field"><label for="civility">Civilité</label><select id="civility" name="civility" required>@foreach (\App\Models\Tenant::CIVILITY_LABELS as $value => $label)<option value="{{ $value }}" @selected(old('civility', $tenant->civility ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="form-field"><label for="status">Statut</label><select id="status" name="status" required>@foreach (\App\Models\Tenant::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected(old('status', $tenant->status ?? \App\Models\Tenant::STATUS_CANDIDATE) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="form-field"><label for="last_name">Nom</label><input id="last_name" name="last_name" value="{{ old('last_name', $tenant->last_name ?? '') }}" required autofocus></div>
        <div class="form-field"><label for="first_name">Prénom</label><input id="first_name" name="first_name" value="{{ old('first_name', $tenant->first_name ?? '') }}" required></div>
        <div class="form-field"><label for="birth_date">Date de naissance <span class="field-optional">(facultatif)</span></label><input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $tenant?->birth_date?->format('Y-m-d')) }}" max="{{ now()->subDay()->format('Y-m-d') }}"></div>
    </div>
    <div class="form-actions"><a class="button secondary" href="{{ route('admin.tenants.index') }}">Annuler</a><button class="button" type="submit">{{ $submitLabel }}</button></div>
</form>
