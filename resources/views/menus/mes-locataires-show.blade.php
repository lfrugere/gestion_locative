@extends('layouts.admin')

@section('title', $tenant->fullName())

@section('content')
    @php($primaryPhoto = $tenant->media->first(fn ($media) => $media->isPhoto() && $media->is_primary))
    <section class="detail-hero">
        <div class="detail-hero-copy">
            <a class="back-link" href="{{ route('mes-locataires') }}">← Mes locataires</a>
            <div class="eyebrow">Locataire</div>
            <h1>{{ $tenant->fullName() }}</h1>
            <p class="detail-lead">{{ $tenant->civilityLabel() }}</p>
        </div>
        @if ($primaryPhoto)
            <figure class="detail-hero-photo"><img src="{{ route('media.download', $primaryPhoto) }}" alt="{{ $primaryPhoto->display_name }}"></figure>
        @endif
        <div class="detail-hero-actions">
            <span class="status-pill status-{{ $tenant->status }}">{{ $tenant->statusLabel() }}</span>
        </div>
    </section>

    <div class="detail-grid detail-grid-single">
        <div class="detail-main">
            <section class="detail-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Identité</span><h2>Informations du locataire</h2></div><span class="panel-icon">◌</span></div>
                <div class="metric-grid tenant-metric-grid">
                    <div class="metric"><span>Civilité</span><strong>{{ $tenant->civilityLabel() }}</strong></div>
                    <div class="metric"><span>Prénom</span><strong>{{ $tenant->first_name }}</strong></div>
                    <div class="metric"><span>Nom</span><strong>{{ $tenant->last_name }}</strong></div>
                    <div class="metric"><span>Date de naissance</span><strong>{{ $tenant->birth_date?->format('d/m/Y') ?? '—' }}</strong></div>
                </div>
            </section>

            <section class="detail-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Patrimoine</span><h2>Biens associés</h2></div><span class="count-badge">{{ $tenant->properties->count() }}</span></div>
                @if ($tenant->properties->isEmpty())
                    <p class="empty compact">Aucun bien n’est associé à ce locataire.</p>
                @else
                    <div class="associated-list">
                        @foreach ($tenant->properties as $associatedProperty)
                            @if ($associatedProperty->isManagedBy(auth()->user()))
                                <a class="associated-row" href="{{ route('mes-biens.show', $associatedProperty) }}">
                                    <span class="entity-mark">{{ $associatedProperty->type === 'parking' ? 'P' : 'A' }}</span>
                                    <span><strong>{{ $associatedProperty->name }}</strong><small>{{ $associatedProperty->reference }}</small></span>
                                    <span class="row-arrow">→</span>
                                </a>
                            @else
                                <div class="associated-row">
                                    <span class="entity-mark">{{ $associatedProperty->type === 'parking' ? 'P' : 'A' }}</span>
                                    <span><strong>{{ $associatedProperty->name }}</strong><small>{{ $associatedProperty->reference }}</small></span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </section>

            @include('admin._media', ['media' => $tenant->media, 'mediable' => $tenant, 'managePermission' => 'manage tenants', 'canManageMedia' => false, 'uploadRoute' => '#', 'singlePhoto' => true])

            @include('admin._notes', ['notes' => $tenant->notes, 'managePermission' => 'manage notes', 'canManageNotes' => false, 'storeRoute' => '#'])
        </div>
    </div>
@endsection
