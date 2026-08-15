@extends('layouts.admin')

@section('title', 'Modifier le locataire')

@section('content')
    <div class="admin-header"><div><p class="muted">Locataires</p><h1>Modifier le locataire</h1></div></div>
    @include('admin.tenants._form', ['action' => route('admin.tenants.update', $tenant), 'submitLabel' => 'Enregistrer les modifications'])
@endsection
