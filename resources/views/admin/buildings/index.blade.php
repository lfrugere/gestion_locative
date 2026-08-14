@extends('layouts.admin')

@section('title', 'Immeubles')

@section('content')
    <div class="admin-header"><div><p class="muted">Patrimoine</p><h1>Immeubles</h1></div>@can('manage buildings')<a class="button" href="{{ route('admin.buildings.create') }}">Ajouter un immeuble</a>@endcan</div>
    <div class="card table-wrap">
        @if ($buildings->isEmpty())<p class="empty">Aucun immeuble n’a encore été créé.</p>@else
            <table><thead><tr><th>Photo</th><th>Référence</th><th>Nom</th><th>Adresse</th><th>Biens</th><th>Actions</th></tr></thead><tbody>
                @foreach ($buildings as $building)<tr><td>@if($building->media->first())<img class="list-thumb" src="{{ route('admin.media.download', $building->media->first()) }}" alt="">@endif</td><td><a href="{{ route('admin.buildings.show', $building) }}"><strong>{{ $building->reference }}</strong></a></td><td><a href="{{ route('admin.buildings.show', $building) }}">{{ $building->name }}</a></td><td>{{ $building->address->line1 }}, {{ $building->address->postal_code }} {{ $building->address->city }}</td><td>{{ $building->properties_count }}</td><td class="actions">@can('manage buildings')<a href="{{ route('admin.buildings.edit', $building) }}">Modifier</a><form method="POST" action="{{ route('admin.buildings.destroy', $building) }}" onsubmit="return confirm('Supprimer cet immeuble ?')">@csrf @method('DELETE')<button class="link-button" type="submit">Supprimer</button></form>@endcan</td></tr>@endforeach
            </tbody></table>
        @endif
    </div>
@endsection
