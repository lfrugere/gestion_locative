@extends('layouts.admin')

@section('title', $building->name)

@section('content')
    @php($primaryPhoto = $building->media->first(fn ($media) => $media->isPhoto() && $media->is_primary))
    @php($user = auth()->user())

    <section class="detail-hero">
        <div class="detail-hero-copy">
            <a class="back-link" href="{{ route('mes-immeubles') }}">← Mes immeubles</a>
            <div class="eyebrow">Immeuble · {{ $building->reference }}</div>
            <h1>{{ $building->name }}</h1>
            <p class="detail-lead">{{ $building->address->line1 }}, {{ $building->address->postal_code }} {{ $building->address->city }}</p>
        </div>
        @if ($primaryPhoto)
            <figure class="detail-hero-photo"><img src="{{ route('media.download', $primaryPhoto) }}" alt="{{ $primaryPhoto->display_name }}"></figure>
        @endif
        <div class="detail-hero-actions">
            <span class="status-pill status-active">{{ $building->properties->count() }} bien{{ $building->properties->count() > 1 ? 's' : '' }}</span>
        </div>
    </section>

    <div class="detail-grid">
        <div class="detail-main">
            <section class="detail-panel address-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Adresse</span><h2>Localisation de l’immeuble</h2></div><span class="panel-icon">⌖</span></div>
                <p class="address-value">{{ $building->address->line1 }}@if($building->address->line2)<br>{{ $building->address->line2 }}@endif<br>{{ $building->address->postal_code }} {{ $building->address->city }}<br>France</p>
            </section>

            <section class="detail-panel associated-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Patrimoine</span><h2>Biens rattachés</h2></div><span class="count-badge">{{ $building->properties->count() }}</span></div>
                @if ($building->properties->isEmpty())
                    <p class="empty compact">Aucun bien n’est encore rattaché à cet immeuble.</p>
                @else
                    <div class="associated-list">
                        @foreach ($building->properties as $property)
                            @php($isManagedProperty = $property->isManagedBy($user))
                            @if ($isManagedProperty)
                                <a class="associated-row" href="{{ route('mes-biens.show', $property) }}">
                                    <span class="entity-mark">{{ $property->type === 'parking' ? 'P' : 'A' }}</span>
                                    <span><strong>{{ $property->name }}</strong><small>{{ $property->reference }} · {{ $property->typeLabel() }}</small></span>
                                    <span class="status-pill {{ $property->status === 'active' ? 'status-active' : 'status-muted' }}">{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</span>
                                    <span class="row-arrow">→</span>
                                </a>
                            @else
                                <div class="associated-row">
                                    <span class="entity-mark">{{ $property->type === 'parking' ? 'P' : 'A' }}</span>
                                    <span><strong>{{ $property->name }}</strong><small>{{ $property->reference }} · {{ $property->typeLabel() }}</small></span>
                                    <span class="status-pill status-muted">Géré par un autre gestionnaire</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </section>

            @include('admin._media', ['media' => $building->media, 'mediable' => $building, 'managePermission' => 'manage buildings', 'canManageMedia' => false, 'uploadRoute' => '#'])

            @include('admin._notes', ['notes' => $building->notes, 'managePermission' => 'manage notes', 'canManageNotes' => false, 'storeRoute' => '#'])
        </div>

        <aside class="detail-aside">
            @include('admin._map', ['address' => $building->address, 'title' => 'Carte'])
        </aside>
    </div>
@endsection
