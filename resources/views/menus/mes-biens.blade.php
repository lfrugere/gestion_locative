@extends('layouts.admin')

@section('title', 'Mes biens')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Menu</p>
            <h1>Mes biens</h1>
        </div>
    </div>

    @forelse ($properties as $property)
        <a class="associated-row room-row" href="{{ route('mes-biens.show', $property) }}">
            <span class="entity-mark">{{ $property->type === 'parking' ? 'P' : ($property->type === 'house' ? 'M' : 'A') }}</span>
            <span>
                <strong>{{ $property->name }}</strong>
                <small>{{ $property->reference }} · {{ $property->typeLabel() }}@if($property->building) · {{ $property->building->name }}@endif</small>
            </span>
            <span class="status-pill {{ $property->status === 'active' ? 'status-active' : 'status-muted' }}">{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</span>
            <span class="row-arrow">→</span>
        </a>
    @empty
        <p class="empty">Aucun bien ne vous a encore été mis en gestion.</p>
    @endforelse
@endsection
