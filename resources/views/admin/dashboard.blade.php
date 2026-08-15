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
                <a class="button secondary" href="{{ route('admin.buildings.create') }}">Ajouter un immeuble</a>
            @endcan
            @can('manage properties')
                <a class="button" href="{{ route('admin.properties.create') }}">Ajouter un bien</a>
            @endcan
        </div>
    </div>

    <section class="dashboard-metrics" aria-label="Synthèse du patrimoine">
        <a class="dashboard-metric" href="{{ route('admin.buildings.index') }}"><span>Immeubles</span><strong>{{ $statistics['buildings'] }}</strong><small>Voir le parc</small></a>
        <a class="dashboard-metric" href="{{ route('admin.properties.index') }}"><span>Biens</span><strong>{{ $statistics['properties'] }}</strong><small>{{ $statistics['activeProperties'] }} actif{{ $statistics['activeProperties'] > 1 ? 's' : '' }}</small></a>
        <a class="dashboard-metric" href="{{ route('admin.properties.index') }}"><span>Appartements</span><strong>{{ $statistics['apartments'] }}</strong><small>Dans le parc</small></a>
        <a class="dashboard-metric" href="{{ route('admin.properties.index') }}"><span>Maisons / parkings</span><strong>{{ $statistics['houses'] + $statistics['parkings'] }}</strong><small>{{ $statistics['houses'] }} maison{{ $statistics['houses'] > 1 ? 's' : '' }} · {{ $statistics['parkings'] }} parking{{ $statistics['parkings'] > 1 ? 's' : '' }}</small></a>
    </section>

    <div class="dashboard-grid">
        <section class="detail-panel dashboard-panel">
            <div class="panel-heading"><div><span class="panel-kicker">À compléter</span><h2>Points d’attention</h2></div><span class="panel-icon">✓</span></div>
            <div class="attention-list">
                <a href="{{ route('admin.properties.index') }}"><span class="attention-count">{{ $attention['propertiesWithoutPhoto'] }}</span><span><strong>bien{{ $attention['propertiesWithoutPhoto'] > 1 ? 's' : '' }} sans photo</strong><small>Ajoutez une photo pour mieux repérer chaque bien.</small></span><span class="row-arrow">→</span></a>
                <a href="{{ route('admin.buildings.index') }}"><span class="attention-count">{{ $attention['buildingsWithoutPhoto'] }}</span><span><strong>immeuble{{ $attention['buildingsWithoutPhoto'] > 1 ? 's' : '' }} sans photo</strong><small>Une photo améliore leur identification dans les listes.</small></span><span class="row-arrow">→</span></a>
                <a href="{{ route('admin.buildings.index') }}"><span class="attention-count">{{ $attention['addressesWithoutCoordinates'] }}</span><span><strong>adresse{{ $attention['addressesWithoutCoordinates'] > 1 ? 's' : '' }} non géocodée{{ $attention['addressesWithoutCoordinates'] > 1 ? 's' : '' }}</strong><small>La carte sera disponible une fois les coordonnées enregistrées.</small></span><span class="row-arrow">→</span></a>
            </div>
        </section>

        <section class="detail-panel dashboard-panel">
            <div class="panel-heading"><div><span class="panel-kicker">Accès rapide</span><h2>Gérer le patrimoine</h2></div><span class="panel-icon">↗</span></div>
            <div class="quick-links">
                <a href="{{ route('admin.buildings.index') }}"><span class="entity-mark">I</span><span><strong>Immeubles</strong><small>Consulter et gérer les bâtiments.</small></span><span class="row-arrow">→</span></a>
                <a href="{{ route('admin.properties.index') }}"><span class="entity-mark">B</span><span><strong>Biens immobiliers</strong><small>Appartements, maisons et parkings.</small></span><span class="row-arrow">→</span></a>
            </div>
        </section>
    </div>

    <div class="dashboard-grid dashboard-lists">
        <section class="detail-panel dashboard-panel">
            <div class="panel-heading"><div><span class="panel-kicker">Derniers ajouts</span><h2>Immeubles récents</h2></div><a class="panel-link" href="{{ route('admin.buildings.index') }}">Tous les immeubles</a></div>
            @forelse ($recentBuildings as $building)
                <a class="dashboard-row" href="{{ route('admin.buildings.show', $building) }}"><span class="entity-mark">I</span><span><strong>{{ $building->name }}</strong><small>{{ $building->reference }} · {{ $building->properties_count }} bien{{ $building->properties_count > 1 ? 's' : '' }}</small></span><span class="row-arrow">→</span></a>
            @empty
                <p class="empty compact">Aucun immeuble pour le moment.</p>
            @endforelse
        </section>

        <section class="detail-panel dashboard-panel">
            <div class="panel-heading"><div><span class="panel-kicker">Derniers ajouts</span><h2>Biens récents</h2></div><a class="panel-link" href="{{ route('admin.properties.index') }}">Tous les biens</a></div>
            @forelse ($recentProperties as $property)
                <a class="dashboard-row" href="{{ route('admin.properties.show', $property) }}"><span class="entity-mark">{{ $property->type === 'parking' ? 'P' : ($property->type === 'house' ? 'M' : 'A') }}</span><span><strong>{{ $property->name }}</strong><small>{{ $property->reference }} · {{ $property->typeLabel() }}@if($property->building) · {{ $property->building->name }}@endif</small></span><span class="status-pill {{ $property->status === 'active' ? 'status-active' : 'status-muted' }}">{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</span><span class="row-arrow">→</span></a>
            @empty
                <p class="empty compact">Aucun bien pour le moment.</p>
            @endforelse
        </section>
    </div>
@endsection
