@extends('layouts.admin')

@section('title', 'Nouveau compte bancaire')

@section('content')
    <div class="admin-header"><div><p class="muted">Admin Locative</p><h1>Ajouter un compte bancaire</h1></div></div>
    @include('admin.bank-accounts._form', ['action' => route('bank-accounts.store'), 'submitLabel' => 'Créer le compte', 'managers' => $managers])
@endsection
