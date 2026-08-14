@extends('layouts.admin')

@section('title', $building->name)

@section('content')
    <div class="admin-header">
        <div><p class="muted">Immeuble</p><h1>{{ $building->name }}</h1><p class="muted">{{ $building->reference }}</p></div>
        <div class="form-actions"><a class="button secondary" href="{{ route('admin.buildings.index') }}">Retour</a>@can('manage buildings')<a class="button" href="{{ route('admin.buildings.edit', $building) }}">Modifier</a>@endcan</div>
    </div>
    <div class="card">
        <h2>Adresse</h2>
        <p>{{ $building->address->line1 }}@if($building->address->line2), {{ $building->address->line2 }}@endif<br>{{ $building->address->postal_code }} {{ $building->address->city }}<br>{{ $building->address->country }}</p>
        @if ($building->notes)<h2>Notes</h2><p>{{ $building->notes }}</p>@endif
    </div>
    <div class="card">
        <h2>Biens rattachés</h2>
        @if ($building->properties->isEmpty())
            <p class="empty">Aucun bien rattaché.</p>
        @else
            <ul>@foreach ($building->properties as $property)<li><a href="{{ route('admin.properties.show', $property) }}">{{ $property->reference }} — {{ $property->name }}</a></li>@endforeach</ul>
        @endif
    </div>
    @can('manage buildings')
        <form method="POST" action="{{ route('admin.buildings.destroy', $building) }}" onsubmit="return confirm('Supprimer cet immeuble ?')">
            @csrf @method('DELETE')
            <button class="button danger" type="submit">Supprimer</button>
        </form>
    @endcan
@endsection
