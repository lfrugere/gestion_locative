@extends('layouts.admin')

@section('title', 'Mes immeubles')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Menu</p>
            <h1>Mes immeubles</h1>
        </div>
    </div>

    <div class="card table-wrap">
        @if ($buildings->isEmpty())
            <p class="empty">Aucun immeuble ne comporte de bien qui vous est mis en gestion.</p>
        @else
            <table>
                <thead>
                    <tr><th>Photo</th><th>Référence</th><th>Nom</th><th>Adresse</th><th>Biens gérés</th></tr>
                </thead>
                <tbody>
                    @foreach ($buildings as $building)
                        <tr>
                            <td>@if($building->media->first())<img class="list-thumb" src="{{ route('media.download', $building->media->first()) }}" alt="">@endif</td>
                            <td><a href="{{ route('mes-immeubles.show', $building) }}"><strong>{{ $building->reference }}</strong></a></td>
                            <td><a href="{{ route('mes-immeubles.show', $building) }}">{{ $building->name }}</a></td>
                            <td>{{ $building->address->line1 }}, {{ $building->address->postal_code }} {{ $building->address->city }}</td>
                            <td>{{ $building->properties_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
