@extends('layouts.admin')

@section('title', $property->name)

@section('content')
    <div class="admin-header">
        <div><p class="muted">{{ $property->typeLabel() }}</p><h1>{{ $property->name }}</h1><p class="muted">{{ $property->reference }}</p></div>
        <div class="form-actions"><a class="button secondary" href="{{ route('admin.properties.index') }}">Retour</a>@can('manage properties')<a class="button" href="{{ route('admin.properties.edit', $property) }}">Modifier</a>@endcan</div>
    </div>
    <div class="card">
        <h2>Informations</h2>
        <p><strong>Type :</strong> {{ $property->typeLabel() }}<br><strong>Statut :</strong> {{ $property->status === 'active' ? 'Actif' : 'Inactif' }}
        @if ($property->floor)<br><strong>Étage :</strong> {{ $property->floor }}@endif
        @if ($property->surface_m2)<br><strong>Surface :</strong> {{ $property->surface_m2 }} m²@endif</p>
        @if ($property->building)
            <h2>Immeuble</h2>
            <p><a href="{{ route('admin.buildings.show', $property->building) }}">{{ $property->building->name }} ({{ $property->building->reference }})</a></p>
        @elseif ($property->address)
            <h2>Adresse</h2>
            <p>{{ $property->address->line1 }}@if($property->address->line2), {{ $property->address->line2 }}@endif<br>{{ $property->address->postal_code }} {{ $property->address->city }}<br>{{ $property->address->country }}</p>
        @endif
        @if ($property->notes)<h2>Notes</h2><p>{{ $property->notes }}</p>@endif
    </div>
    @can('manage properties')
        <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" onsubmit="return confirm('Supprimer ce bien ?')">
            @csrf @method('DELETE')
            <button class="button danger" type="submit">Supprimer</button>
        </form>
    @endcan
@endsection
