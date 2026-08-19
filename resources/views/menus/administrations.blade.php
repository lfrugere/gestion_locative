@extends('layouts.admin')

@section('title', 'Admin Général')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Menu</p>
            <h1>Admin Général</h1>
        </div>
    </div>

    <div class="quick-links">
        @can('manage system')
            <a href="{{ route('system-checks.index') }}"><span class="entity-mark">C</span><span><strong>Configuration</strong><small>Vérifications système.</small></span><span class="row-arrow">→</span></a>
        @endcan
    </div>
    @cannot('manage system')
        <p class="empty">Cette section est vide pour l’instant.</p>
    @endcannot
@endsection
