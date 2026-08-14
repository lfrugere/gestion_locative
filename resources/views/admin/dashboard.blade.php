@extends('layouts.admin')

@section('title', 'Administration')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Espace d’administration</p>
            <h1>Vue d’ensemble</h1>
        </div>
    </div>

    <div class="card">
        <h2>Bienvenue, {{ auth()->user()->name }}</h2>
        <p class="muted">Créez vos immeubles et vos biens immobiliers depuis les rubriques ci-dessous.</p>
    </div>
@endsection
