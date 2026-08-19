@extends('layouts.admin')

@section('title', 'Nouvelle pièce')

@section('content')
    <div class="admin-header"><div><p class="muted">{{ $property->name }}</p><h1>Ajouter une pièce</h1></div></div>
    @include('admin.property-rooms._form', ['action' => route('property-rooms.store', $property), 'submitLabel' => 'Créer la pièce'])
@endsection
