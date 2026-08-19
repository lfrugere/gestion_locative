@extends('layouts.admin')

@section('title', 'Modifier le bien')

@section('content')
    <div class="admin-header"><div><p class="muted">Patrimoine</p><h1>Modifier le bien</h1></div></div>
    @include('admin.properties._form', ['action' => route('properties.update', $property), 'submitLabel' => 'Enregistrer les modifications'])
@endsection
