@extends('layouts.admin')

@section('title', 'Locataires')

@section('content')
    <div class="admin-header">
        <div><p class="muted">Gestion locative</p><h1>Locataires</h1></div>
        @can('manage tenants')<a class="button" href="{{ route('tenants.create') }}">Ajouter un locataire</a>@endcan
    </div>

    <form class="list-filter" method="GET" action="{{ route('tenants.index') }}">
        <label for="status-filter">Afficher les dossiers</label>
        <select id="status-filter" name="status" onchange="this.form.submit()">
            @foreach (\App\Models\Tenant::STATUS_LABELS as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}{{ $value === \App\Models\Tenant::STATUS_ACTIVE ? ' (par défaut)' : '' }}</option>
            @endforeach
        </select>
        <button class="button secondary" type="submit">Filtrer</button>
    </form>

    <div class="card table-wrap">
        @if ($tenants->isEmpty())
            <p class="empty">Aucun locataire {{ mb_strtolower(\App\Models\Tenant::STATUS_LABELS[$status]) }} n’a été trouvé.</p>
        @else
            <table>
                <thead><tr><th>Photo</th><th>Nom</th><th>Civilité</th><th>Date de naissance</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach ($tenants as $tenant)
                        @php($primaryPhoto = $tenant->media->first())
                        <tr>
                            <td>@if($primaryPhoto)<img class="list-thumb" src="{{ route('media.download', $primaryPhoto) }}" alt="">@endif</td>
                            <td><a href="{{ route('tenants.show', $tenant) }}"><strong>{{ $tenant->fullName() }}</strong></a></td>
                            <td>{{ $tenant->civilityLabel() }}</td>
                            <td>{{ $tenant->birth_date?->format('d/m/Y') ?? '—' }}</td>
                            <td><span class="status-pill status-{{ $tenant->status }}">{{ $tenant->statusLabel() }}</span></td>
                            <td class="actions">
                                @can('manage tenants')
                                    <a class="icon-action" href="{{ route('tenants.edit', $tenant) }}" aria-label="Modifier {{ $tenant->fullName() }}" data-tooltip="Modifier" title="Modifier"><span aria-hidden="true">✎</span></a>
                                    <form method="POST" action="{{ route('tenants.destroy', $tenant) }}" onsubmit="return confirm('Supprimer ce locataire ?')">@csrf @method('DELETE')<button class="icon-action danger-action" type="submit" aria-label="Supprimer {{ $tenant->fullName() }}" data-tooltip="Supprimer" title="Supprimer"><span aria-hidden="true">×</span></button></form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
