@extends('layouts.admin')

@section('title', 'Modifier la pièce')

@section('content')
    <div class="admin-header"><div><p class="muted">{{ $property->name }}</p><h1>Modifier la pièce</h1></div></div>
    @include('admin.property-rooms._form', ['action' => route('property-rooms.update', [$property, $room]), 'submitLabel' => 'Enregistrer les modifications'])
@endsection
