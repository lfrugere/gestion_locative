@extends('layouts.admin')

@section('title', 'Configuration')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Administration</p>
            <h1>Configuration du serveur</h1>
            <p class="dashboard-lead">Vérifiez les prérequis du serveur ou du conteneur avant une mise en service.</p>
        </div>
    </div>

    <section class="detail-panel checklist-panel">
        <div class="panel-heading"><div><span class="panel-kicker">Checklist</span><h2>État de la configuration</h2></div><span class="panel-icon">✓</span></div>
        <div class="checklist">
            @foreach ($checks as $check)
                <article class="check-row {{ $check['ok'] ? 'check-ok' : 'check-failed' }}">
                    <span class="check-status" aria-label="{{ $check['ok'] ? 'Conforme' : 'À corriger' }}">{{ $check['ok'] ? '✓' : '!' }}</span>
                    <div><strong>{{ $check['label'] }}</strong><small>{{ $check['hint'] }}</small></div>
                    <code>{{ $check['value'] ?: 'Non défini' }}</code>
                </article>
            @endforeach
        </div>
    </section>
@endsection
