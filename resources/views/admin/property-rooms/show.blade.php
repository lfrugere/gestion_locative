@extends('layouts.admin')

@section('title', $room->name)

@section('content')
    @php($primaryPhoto = $room->media->first(fn ($media) => $media->isPhoto() && $media->is_primary))

    <section class="detail-hero">
        <div class="detail-hero-copy">
            <a class="back-link" href="{{ route('properties.show', $property) }}">← {{ $property->name }}</a>
            <div class="eyebrow">{{ $property->reference }}</div>
            <h1>{{ $room->name }}</h1>
            <p class="detail-lead">{{ $property->name }} · Colocation</p>
        </div>
        @if ($primaryPhoto)
            <figure class="detail-hero-photo"><img src="{{ route('media.download', $primaryPhoto) }}" alt="{{ $primaryPhoto->display_name }}"></figure>
        @endif
        <div class="detail-hero-actions">
            <span class="status-pill {{ $room->status === 'active' ? 'status-active' : 'status-muted' }}">{{ $room->status === 'active' ? 'Active' : 'Inactive' }}</span>
            @can('manage properties')
                <a class="button secondary" href="{{ route('property-rooms.edit', [$property, $room]) }}">Modifier</a>
            @endcan
        </div>
    </section>

    <div class="detail-grid detail-grid-single">
        <div class="detail-main">
            <section class="detail-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Vue d’ensemble</span><h2>Caractéristiques de la pièce</h2></div><span class="panel-icon">▦</span></div>
                <div class="metric-grid">
                    <div class="metric"><span>Statut</span><strong>{{ $room->status === 'active' ? 'Active' : 'Inactive' }}</strong></div>
                    <div class="metric"><span>Surface</span><strong>{{ $room->surface_m2 ? $room->surface_m2.' m²' : '—' }}</strong></div>
                    <div class="metric"><span>Bien</span><strong>{{ $property->reference }}</strong></div>
                </div>
            </section>

            @include('admin._media', ['media' => $room->media, 'mediable' => $room, 'managePermission' => 'manage properties', 'uploadRoute' => route('property-rooms.media.store', [$property, $room])])

            @include('admin._notes', ['notes' => $room->notes, 'managePermission' => 'manage properties', 'storeRoute' => route('property-rooms.notes.store', [$property, $room])])

            @can('manage properties')
                <form class="danger-zone" method="POST" action="{{ route('property-rooms.destroy', [$property, $room]) }}" onsubmit="return confirm('Supprimer cette pièce ?')">
                    @csrf @method('DELETE')
                    <div><strong>Supprimer la pièce</strong><p>Les pièces jointes et les photos associées seront également supprimées.</p></div>
                    <button class="button danger" type="submit">Supprimer</button>
                </form>
            @endcan
        </div>
    </div>
@endsection
