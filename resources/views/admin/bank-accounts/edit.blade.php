@extends('layouts.admin')

@section('title', 'Modifier le compte bancaire')

@section('content')
    <div class="admin-header"><div><p class="muted">Admin Locative</p><h1>Modifier le compte bancaire</h1></div></div>
    @include('admin.bank-accounts._form', ['action' => route('bank-accounts.update', $bankAccount), 'submitLabel' => 'Enregistrer les modifications', 'bankAccount' => $bankAccount, 'managers' => $managers])
@endsection
