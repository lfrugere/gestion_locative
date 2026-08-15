@extends('layouts.admin')

@section('title', $property->name)

@section('content')
    @php($mapAddress = $property->building?->address ?? $property->address)
    @php($primaryPhoto = $property->media->first(fn ($media) => $media->isPhoto() && $media->is_primary))

    <section class="detail-hero">
        <div class="detail-hero-copy">
            <a class="back-link" href="{{ route('admin.properties.index') }}">← Tous les biens</a>
            <div class="eyebrow">{{ $property->typeLabel() }} · {{ $property->reference }}</div>
            <h1>{{ $property->name }}</h1>
            <p class="detail-lead">{{ $property->building?->name ?? $mapAddress?->city ?? 'Adresse à compléter' }}</p>
        </div>
        <div class="detail-hero-actions">
            <span class="status-pill {{ $property->status === 'active' ? 'status-active' : 'status-muted' }}">{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</span>
            @can('manage properties')
                <a class="button secondary" href="{{ route('admin.properties.edit', $property) }}">Modifier</a>
            @endcan
        </div>
    </section>

    <div class="detail-grid">
        <div class="detail-main">
            <section class="detail-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Vue d’ensemble</span><h2>Caractéristiques du bien</h2></div><span class="panel-icon">⌂</span></div>
                <div class="metric-grid">
                    <div class="metric"><span>Type</span><strong>{{ $property->typeLabel() }}</strong></div>
                    <div class="metric"><span>Statut</span><strong>{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</strong></div>
                    <div class="metric"><span>Étage</span><strong>{{ $property->floor ?: '—' }}</strong></div>
                    <div class="metric"><span>Surface</span><strong>{{ $property->surface_m2 ? $property->surface_m2.' m²' : '—' }}</strong></div>
                </div>
            </section>

            <section class="detail-panel address-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Rattachement</span><h2>{{ $property->building ? 'Immeuble' : 'Adresse' }}</h2></div><span class="panel-icon">⌖</span></div>
                @if ($property->building)
                    <a class="related-entity" href="{{ route('admin.buildings.show', $property->building) }}"><span class="entity-mark">I</span><span><strong>{{ $property->building->name }}</strong><small>{{ $property->building->reference }} · {{ $mapAddress->line1 }}, {{ $mapAddress->city }}</small></span><span class="row-arrow">→</span></a>
                @elseif ($property->address)
                    <p class="address-value">{{ $property->address->line1 }}@if($property->address->line2)<br>{{ $property->address->line2 }}@endif<br>{{ $property->address->postal_code }} {{ $property->address->city }}<br>{{ $property->address->country }}</p>
                @endif
                @if ($property->notes)<div class="notes-block"><span>Notes</span><p>{{ $property->notes }}</p></div>@endif
            </section>

            @include('admin._media', ['media' => $property->media, 'managePermission' => 'manage properties', 'uploadRoute' => route('admin.properties.media.store', $property)])

            @can('manage properties')
                <form class="danger-zone" method="POST" action="{{ route('admin.properties.destroy', $property) }}" onsubmit="return confirm('Supprimer ce bien ?')">
                    @csrf @method('DELETE')
                    <div><strong>Supprimer le bien</strong><p>Les pièces jointes et les photos associées seront également supprimées.</p></div>
                    <button class="button danger" type="submit">Supprimer</button>
                </form>
            @endcan
        </div>

        <aside class="detail-aside">
            @include('admin._map', ['address' => $mapAddress, 'title' => 'Carte'])
            @if ($primaryPhoto)
                <section class="cover-card"><img src="{{ route('admin.media.download', $primaryPhoto) }}" alt="{{ $primaryPhoto->display_name }}"><div><span>Photo principale</span><strong>{{ $primaryPhoto->display_name }}</strong></div></section>
            @endif
        </aside>
    </div>
@endsection
