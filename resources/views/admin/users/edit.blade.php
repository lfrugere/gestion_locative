@extends('layouts.admin')

@section('title', 'Modifier l’utilisateur')

@section('content')
    <div class="admin-header"><div><p class="muted">Utilisateurs</p><h1>Modifier l’utilisateur</h1></div></div>
    @include('admin.users._form', ['action' => route('users.update', $user), 'submitLabel' => 'Enregistrer les modifications'])
@endsection
