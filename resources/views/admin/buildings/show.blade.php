@extends('layouts.admin')

@section('title', $building->name)

@section('content')
    @php($primaryPhoto = $building->media->first(fn ($media) => $media->isPhoto() && $media->is_primary))

    <section class="detail-hero">
        <div class="detail-hero-copy">
            <a class="back-link" href="{{ route('admin.buildings.index') }}">← Tous les immeubles</a>
            <div class="eyebrow">Immeuble · {{ $building->reference }}</div>
            <h1>{{ $building->name }}</h1>
            <p class="detail-lead">{{ $building->address->line1 }}, {{ $building->address->postal_code }} {{ $building->address->city }}</p>
        </div>
        @if ($primaryPhoto)
            <figure class="detail-hero-photo"><img src="{{ route('admin.media.download', $primaryPhoto) }}" alt="{{ $primaryPhoto->display_name }}"></figure>
        @endif
        <div class="detail-hero-actions">
            <span class="status-pill status-active">{{ $building->properties->count() }} bien{{ $building->properties->count() > 1 ? 's' : '' }}</span>
            @can('manage buildings')
                <a class="button secondary" href="{{ route('admin.buildings.edit', $building) }}">Modifier</a>
            @endcan
        </div>
    </section>

    <div class="detail-grid">
        <div class="detail-main">
            <section class="detail-panel address-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Adresse</span><h2>Localisation de l’immeuble</h2></div><span class="panel-icon">⌖</span></div>
                <p class="address-value">{{ $building->address->line1 }}@if($building->address->line2)<br>{{ $building->address->line2 }}@endif<br>{{ $building->address->postal_code }} {{ $building->address->city }}<br>{{ $building->address->country }}</p>
            </section>

            <section class="detail-panel associated-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Patrimoine</span><h2>Biens rattachés</h2></div><span class="count-badge">{{ $building->properties->count() }}</span></div>
                @if ($building->properties->isEmpty())
                    <p class="empty compact">Aucun bien n’est encore rattaché à cet immeuble.</p>
                @else
                    <div class="associated-list">
                        @foreach ($building->properties as $property)
                            <a class="associated-row" href="{{ route('admin.properties.show', $property) }}">
                                <span class="entity-mark">{{ $property->type === 'parking' ? 'P' : 'A' }}</span>
                                <span><strong>{{ $property->name }}</strong><small>{{ $property->reference }} · {{ $property->typeLabel() }}@if($property->floor) · Étage {{ $property->floor }}@endif</small></span>
                                <span class="status-pill {{ $property->status === 'active' ? 'status-active' : 'status-muted' }}">{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</span>
                                <span class="row-arrow">→</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            @include('admin._media', ['media' => $building->media, 'mediable' => $building, 'managePermission' => 'manage buildings', 'uploadRoute' => route('admin.buildings.media.store', $building)])

            @include('admin._notes', ['notes' => $building->notes, 'managePermission' => 'manage buildings', 'storeRoute' => route('admin.buildings.notes.store', $building)])

            @can('manage buildings')
                <form class="danger-zone" method="POST" action="{{ route('admin.buildings.destroy', $building) }}" onsubmit="return confirm('Supprimer cet immeuble ?')">
                    @csrf @method('DELETE')
                    <div><strong>Supprimer l’immeuble</strong><p>Cette action est possible uniquement lorsqu’aucun bien n’est rattaché.</p></div>
                    <button class="button danger" type="submit">Supprimer</button>
                </form>
            @endcan
        </div>

        <aside class="detail-aside">
            @include('admin._map', ['address' => $building->address, 'title' => 'Carte'])
        </aside>
    </div>
@endsection
