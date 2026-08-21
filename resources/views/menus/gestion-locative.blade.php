@extends('layouts.admin')

@section('title', 'Gestion Locative')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Menu</p>
            <h1>Gestion Locative</h1>
        </div>
    </div>

    <div class="quick-links">
        @can('viewAny', \App\Models\Property::class)
            <a href="{{ route('mes-biens') }}"><span class="entity-mark">B</span><span><strong>Mes biens</strong><small>Les biens qui vous sont mis en gestion.</small></span><span class="row-arrow">→</span></a>
        @endcan
        @hasrole('gestionnaire')
            <a href="{{ route('mes-immeubles') }}"><span class="entity-mark">I</span><span><strong>Mes immeubles</strong><small>Les immeubles comportant un bien qui vous est mis en gestion.</small></span><span class="row-arrow">→</span></a>
            <a href="{{ route('mes-locataires') }}"><span class="entity-mark">L</span><span><strong>Mes locataires</strong><small>Les locataires rattachés à un bien qui vous est mis en gestion.</small></span><span class="row-arrow">→</span></a>
            <a href="{{ route('mes-comptes-bancaires') }}"><span class="entity-mark">€</span><span><strong>Mes comptes bancaires</strong><small>Les comptes bancaires rattachés à un bien qui vous est mis en gestion.</small></span><span class="row-arrow">→</span></a>
        @endhasrole
        <a href="{{ route('mes-contrats') }}"><span class="entity-mark">C</span><span><strong>Mes contrats</strong><small>Cette section est vide pour l’instant.</small></span><span class="row-arrow">→</span></a>
    </div>
@endsection
