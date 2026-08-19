@extends('layouts.admin')

@section('title', 'Modifier l’immeuble')

@section('content')
    <div class="admin-header"><div><p class="muted">Patrimoine</p><h1>Modifier l’immeuble</h1></div></div>
    @include('admin.buildings._form', ['action' => route('buildings.update', $building), 'submitLabel' => 'Enregistrer les modifications', 'building' => $building])
@endsection
