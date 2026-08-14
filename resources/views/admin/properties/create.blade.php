@extends('layouts.admin')

@section('title', 'Nouveau bien')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Patrimoine</p>
            <h1>Ajouter un bien</h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="errors" role="alert">
            <strong>Veuillez corriger les erreurs suivantes :</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form class="card" method="POST" action="{{ route('admin.properties.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-field">
                <label for="reference">Référence</label>
                <input id="reference" name="reference" value="{{ old('reference') }}" required autofocus>
            </div>
            <div class="form-field">
                <label for="name">Nom du bien</label>
                <input id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-field">
                <label for="type">Type</label>
                <select id="type" name="type" required>
                    <option value="">Sélectionner</option>
                    @foreach (\App\Models\Property::TYPE_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="hint">Un appartement ou un parking doit être rattaché à un immeuble.</p>
            </div>
            <div class="form-field">
                <label for="building_id">Immeuble</label>
                <select id="building_id" name="building_id">
                    <option value="">Aucun — maison indépendante</option>
                    @foreach ($buildings as $building)
                        <option value="{{ $building->id }}" @selected((string) old('building_id') === (string) $building->id)>{{ $building->name }} ({{ $building->reference }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label for="floor">Étage</label>
                <input id="floor" name="floor" value="{{ old('floor') }}" placeholder="Rez-de-chaussée, 1, 2…">
            </div>
            <div class="form-field">
                <label for="surface_m2">Surface (m²)</label>
                <input id="surface_m2" name="surface_m2" type="number" min="0" step="0.01" value="{{ old('surface_m2') }}">
            </div>
            <div class="form-field">
                <label for="status">Statut</label>
                <select id="status" name="status" required>
                    <option value="active" @selected(old('status', 'active') === 'active')>Actif</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>Inactif</option>
                </select>
            </div>
            <div class="form-field full"><h2>Adresse propre à la maison</h2><p class="hint">Ces champs sont requis uniquement pour une maison indépendante.</p></div>
            <div class="form-field full">
                <label for="address_line1">Adresse</label>
                <input id="address_line1" name="address[line1]" value="{{ old('address.line1') }}">
            </div>
            <div class="form-field">
                <label for="address_line2">Complément</label>
                <input id="address_line2" name="address[line2]" value="{{ old('address.line2') }}">
            </div>
            <div class="form-field">
                <label for="address_postal_code">Code postal</label>
                <input id="address_postal_code" name="address[postal_code]" value="{{ old('address.postal_code') }}">
            </div>
            <div class="form-field">
                <label for="address_city">Ville</label>
                <input id="address_city" name="address[city]" value="{{ old('address.city') }}">
            </div>
            <div class="form-field">
                <label for="address_country">Pays</label>
                <input id="address_country" name="address[country]" value="{{ old('address.country', 'FR') }}" maxlength="2">
            </div>
            <div class="form-field full">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a class="button secondary" href="{{ route('admin.properties.index') }}">Annuler</a>
            <button class="button" type="submit">Créer le bien</button>
        </div>
    </form>
@endsection
