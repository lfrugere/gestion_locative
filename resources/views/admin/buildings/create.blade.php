@extends('layouts.admin')

@section('title', 'Nouvel immeuble')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Patrimoine</p>
            <h1>Ajouter un immeuble</h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="errors" role="alert">
            <strong>Veuillez corriger les erreurs suivantes :</strong>
            <ul>
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form class="card" method="POST" action="{{ route('admin.buildings.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-field">
                <label for="reference">Référence</label>
                <input id="reference" name="reference" value="{{ old('reference') }}" required autofocus>
            </div>
            <div class="form-field">
                <label for="name">Nom de l’immeuble</label>
                <input id="name" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-field full"><h2>Adresse</h2></div>
            <div class="form-field full">
                <label for="address_line1">Adresse</label>
                <input id="address_line1" name="address[line1]" value="{{ old('address.line1') }}" required>
            </div>
            <div class="form-field">
                <label for="address_line2">Complément</label>
                <input id="address_line2" name="address[line2]" value="{{ old('address.line2') }}">
            </div>
            <div class="form-field">
                <label for="address_postal_code">Code postal</label>
                <input id="address_postal_code" name="address[postal_code]" value="{{ old('address.postal_code') }}" required>
            </div>
            <div class="form-field">
                <label for="address_city">Ville</label>
                <input id="address_city" name="address[city]" value="{{ old('address.city') }}" required>
            </div>
            <div class="form-field">
                <label for="address_country">Pays</label>
                <input id="address_country" name="address[country]" value="{{ old('address.country', 'FR') }}" maxlength="2" required>
            </div>
            <div class="form-field full">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="form-actions">
            <a class="button secondary" href="{{ route('admin.buildings.index') }}">Annuler</a>
            <button class="button" type="submit">Créer l’immeuble</button>
        </div>
    </form>
@endsection
