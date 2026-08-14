@extends('layouts.admin')

@section('title', 'Nouvel immeuble')

@section('content')
    <div class="admin-header"><div><p class="muted">Patrimoine</p><h1>Ajouter un immeuble</h1></div></div>
    @include('admin.buildings._form', ['action' => route('admin.buildings.store'), 'submitLabel' => 'Créer l’immeuble'])
@endsection
