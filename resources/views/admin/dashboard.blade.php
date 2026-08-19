@extends('layouts.admin')

@section('title', 'Vue d’ensemble')

@section('content')
    <div class="admin-header dashboard-header">
        <div>
            <p class="muted">Espace d’administration</p>
            <h1>Vue d’ensemble</h1>
            <p class="dashboard-lead">Un aperçu de votre patrimoine et des prochaines actions utiles.</p>
        </div>
        <div class="dashboard-actions">
            @can('manage buildings')
                <a class="button secondary" href="{{ route('buildings.create') }}">Ajouter un immeuble</a>
            @endcan
            @can('manage properties')
                <a class="button" href="{{ route('properties.create') }}">Ajouter un bien</a>
            @endcan
            @can('manage tenants')
                <a class="button secondary" href="{{ route('tenants.create') }}">Ajouter un locataire</a>
            @endcan
        </div>
    </div>

    <section class="dashboard-metrics" aria-label="Synthèse du patrimoine">
        <a class="dashboard-metric" href="{{ route('buildings.index') }}"><span>Immeubles</span><strong>{{ $statistics['buildings'] }}</strong><small>Voir le parc</small></a>
        <a class="dashboard-metric" href="{{ route('properties.index') }}"><span>Biens</span><strong>{{ $statistics['properties'] }}</strong><small>{{ $statistics['activeProperties'] }} actif{{ $statistics['activeProperties'] > 1 ? 's' : '' }}</small></a>
        <a class="dashboard-metric" href="{{ route('properties.index') }}"><span>Appartements</span><strong>{{ $statistics['apartments'] }}</strong><small>Dans le parc</small></a>
        <a class="dashboard-metric" href="{{ route('properties.index') }}"><span>Maisons / parkings</span><strong>{{ $statistics['houses'] + $statistics['parkings'] }}</strong><small>{{ $statistics['houses'] }} maison{{ $statistics['houses'] > 1 ? 's' : '' }} · {{ $statistics['parkings'] }} parking{{ $statistics['parkings'] > 1 ? 's' : '' }}</small></a>
        @can('view tenants')
            <a class="dashboard-metric" href="{{ route('tenants.index') }}"><span>Locataires</span><strong>{{ $statistics['tenants'] }}</strong><small>Consulter les dossiers</small></a>
        @endcan
    </section>

    <div class="dashboard-grid">
        @canany(['manage buildings', 'manage properties'])
            <section class="detail-panel dashboard-panel">
                <div class="panel-heading"><div><span class="panel-kicker">À compléter</span><h2>Points d’attention</h2></div><span class="panel-icon">✓</span></div>
                <div class="attention-list">
                    <a href="{{ route('properties.index') }}"><span class="attention-count">{{ $attention['propertiesWithoutPhoto'] }}</span><span><strong>bien{{ $attention['propertiesWithoutPhoto'] > 1 ? 's' : '' }} sans photo</strong><small>Ajoutez une photo pour mieux repérer chaque bien.</small></span><span class="row-arrow">→</span></a>
                    <a href="{{ route('buildings.index') }}"><span class="attention-count">{{ $attention['buildingsWithoutPhoto'] }}</span><span><strong>immeuble{{ $attention['buildingsWithoutPhoto'] > 1 ? 's' : '' }} sans photo</strong><small>Une photo améliore leur identification dans les listes.</small></span><span class="row-arrow">→</span></a>
                    <a href="{{ route('buildings.index') }}"><span class="attention-count">{{ $attention['addressesWithoutCoordinates'] }}</span><span><strong>adresse{{ $attention['addressesWithoutCoordinates'] > 1 ? 's' : '' }} non géocodée{{ $attention['addressesWithoutCoordinates'] > 1 ? 's' : '' }}</strong><small>La carte sera disponible une fois les coordonnées enregistrées.</small></span><span class="row-arrow">→</span></a>
                </div>
            </section>
        @endcanany

        <section class="detail-panel dashboard-panel">
            <div class="panel-heading"><div><span class="panel-kicker">Accès rapide</span><h2>Consulter le patrimoine</h2></div><span class="panel-icon">↗</span></div>
            <div class="quick-links">
                <a href="{{ route('buildings.index') }}"><span class="entity-mark">I</span><span><strong>Immeubles</strong><small>Consulter les bâtiments et laisser des notes.</small></span><span class="row-arrow">→</span></a>
                <a href="{{ route('properties.index') }}"><span class="entity-mark">B</span><span><strong>Biens immobiliers</strong><small>Appartements, maisons et parkings.</small></span><span class="row-arrow">→</span></a>
                @can('view tenants')
                    <a href="{{ route('tenants.index') }}"><span class="entity-mark">L</span><span><strong>Locataires</strong><small>Consulter les dossiers locataires.</small></span><span class="row-arrow">→</span></a>
                @endcan
            </div>
        </section>
    </div>

    <div class="dashboard-grid dashboard-lists">
        <section class="detail-panel dashboard-panel">
            <div class="panel-heading"><div><span class="panel-kicker">Derniers ajouts</span><h2>Immeubles récents</h2></div><a class="panel-link" href="{{ route('buildings.index') }}">Tous les immeubles</a></div>
            @forelse ($recentBuildings as $building)
                <a class="dashboard-row" href="{{ route('buildings.show', $building) }}"><span class="entity-mark">I</span><span><strong>{{ $building->name }}</strong><small>{{ $building->reference }} · {{ $building->properties_count }} bien{{ $building->properties_count > 1 ? 's' : '' }}</small></span><span class="row-arrow">→</span></a>
            @empty
                <p class="empty compact">Aucun immeuble pour le moment.</p>
            @endforelse
        </section>

        <section class="detail-panel dashboard-panel">
            <div class="panel-heading"><div><span class="panel-kicker">Derniers ajouts</span><h2>Biens récents</h2></div><a class="panel-link" href="{{ route('properties.index') }}">Tous les biens</a></div>
            @forelse ($recentProperties as $property)
                <a class="dashboard-row" href="{{ route('properties.show', $property) }}">
                    <span class="entity-mark">{{ $property->type === 'parking' ? 'P' : ($property->type === 'house' ? 'M' : 'A') }}</span>
                    <span>
                        <strong>{{ $property->name }}</strong>
                        <small>
                            {{ $property->reference }} · {{ $property->typeLabel() }}
                            @if ($property->is_shared_accommodation)
                                · Colocation
                            @endif
                            @if ($property->building)
                                · {{ $property->building->name }}
                            @endif
                        </small>
                    </span>
                    <span class="status-pill {{ $property->status === 'active' ? 'status-active' : 'status-muted' }}">{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</span>
                    <span class="row-arrow">→</span>
                </a>
            @empty
                <p class="empty compact">Aucun bien pour le moment.</p>
            @endforelse
        </section>

        @can('view tenants')
            <section class="detail-panel dashboard-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Derniers ajouts</span><h2>Locataires récents</h2></div><a class="panel-link" href="{{ route('tenants.index') }}">Tous les locataires</a></div>
                @forelse ($recentTenants as $tenant)
                    <a class="dashboard-row" href="{{ route('tenants.show', $tenant) }}"><span class="entity-mark">L</span><span><strong>{{ $tenant->fullName() }}</strong><small>{{ $tenant->civilityLabel() }}</small></span><span class="status-pill status-{{ $tenant->status }}">{{ $tenant->statusLabel() }}</span><span class="row-arrow">→</span></a>
                @empty
                    <p class="empty compact">Aucun locataire pour le moment.</p>
                @endforelse
            </section>
        @endcan
    </div>
@endsection
