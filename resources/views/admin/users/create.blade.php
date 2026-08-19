@extends('layouts.admin')

@section('title', 'Nouvel utilisateur')

@section('content')
    <div class="admin-header"><div><p class="muted">Utilisateurs</p><h1>Ajouter un utilisateur</h1></div></div>
    @include('admin.users._form', ['action' => route('users.store'), 'submitLabel' => 'Créer l’utilisateur', 'user' => null])
@endsection
