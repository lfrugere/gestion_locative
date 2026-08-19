@extends('layouts.admin')

@section('title', 'Biens')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Patrimoine</p>
            <h1>Biens immobiliers</h1>
        </div>
        @can('manage properties')
            <a class="button" href="{{ route('properties.create') }}">Ajouter un bien</a>
        @endcan
    </div>

    <div class="card table-wrap">
        @if ($properties->isEmpty())
            <p class="empty">Aucun bien n’a encore été créé.</p>
        @else
            <table>
                <thead>
                    <tr><th>Photo</th><th>Référence</th><th>Type</th><th>Nom</th><th>Immeuble / adresse</th><th>Usage</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach ($properties as $property)
                        <tr>
                            <td>@if($property->media->first())<img class="list-thumb" src="{{ route('media.download', $property->media->first()) }}" alt="">@endif</td>
                            <td><a href="{{ route('properties.show', $property) }}"><strong>{{ $property->reference }}</strong></a></td>
                            <td>{{ $property->typeLabel() }}</td>
                            <td><a href="{{ route('properties.show', $property) }}">{{ $property->name }}</a></td>
                            <td>
                                @if ($property->building)
                                    {{ $property->building->name }}
                                @elseif ($property->address)
                                    {{ $property->address->line1 }}, {{ $property->address->postal_code }} {{ $property->address->city }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>@if($property->is_shared_accommodation)<span class="status-pill status-active">Colocation</span>@else<span class="muted">Classique</span>@endif</td>
                            <td>{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</td>
                            <td class="actions">
                                @can('manage properties')
                                    <a class="icon-action" href="{{ route('properties.edit', $property) }}" aria-label="Modifier {{ $property->name }}" data-tooltip="Modifier" title="Modifier"><span aria-hidden="true">✎</span></a>
                                    <form method="POST" action="{{ route('properties.destroy', $property) }}" onsubmit="return confirm('Supprimer ce bien ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-action danger-action" type="submit" aria-label="Supprimer {{ $property->name }}" data-tooltip="Supprimer" title="Supprimer"><span aria-hidden="true">×</span></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
