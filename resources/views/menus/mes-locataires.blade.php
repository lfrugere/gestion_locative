@extends('layouts.admin')

@section('title', 'Mes locataires')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Menu</p>
            <h1>Mes locataires</h1>
        </div>
    </div>

    <div class="card table-wrap">
        @if ($tenants->isEmpty())
            <p class="empty">Aucun locataire n’est rattaché à un bien qui vous est mis en gestion.</p>
        @else
            <table>
                <thead><tr><th>Photo</th><th>Nom</th><th>Civilité</th><th>Date de naissance</th><th>Statut</th></tr></thead>
                <tbody>
                    @foreach ($tenants as $tenant)
                        @php($primaryPhoto = $tenant->media->first())
                        <tr>
                            <td>@if($primaryPhoto)<img class="list-thumb" src="{{ route('media.download', $primaryPhoto) }}" alt="">@endif</td>
                            <td><a href="{{ route('mes-locataires.show', $tenant) }}"><strong>{{ $tenant->fullName() }}</strong></a></td>
                            <td>{{ $tenant->civilityLabel() }}</td>
                            <td>{{ $tenant->birth_date?->format('d/m/Y') ?? '—' }}</td>
                            <td><span class="status-pill status-{{ $tenant->status }}">{{ $tenant->statusLabel() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
