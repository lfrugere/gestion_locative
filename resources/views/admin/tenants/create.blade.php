@extends('layouts.admin')

@section('title', 'Nouveau locataire')

@section('content')
    <div class="admin-header"><div><p class="muted">Locataires</p><h1>Ajouter un locataire</h1></div></div>
    @include('admin.tenants._form', ['action' => route('tenants.store'), 'submitLabel' => 'Créer le locataire', 'tenant' => null])
@endsection
