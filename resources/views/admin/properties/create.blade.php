@extends('layouts.admin')

@section('title', 'Nouveau bien')

@section('content')
    <div class="admin-header"><div><p class="muted">Patrimoine</p><h1>Ajouter un bien</h1></div></div>
    @include('admin.properties._form', ['action' => route('properties.store'), 'submitLabel' => 'Créer le bien', 'property' => null])
@endsection
