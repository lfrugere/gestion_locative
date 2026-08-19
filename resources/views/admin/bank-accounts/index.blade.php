@extends('layouts.admin')

@section('title', 'Comptes Bancaires')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Admin Locative</p>
            <h1>Comptes Bancaires</h1>
        </div>
        @can('manage bank accounts')
            <a class="button" href="{{ route('bank-accounts.create') }}">Ajouter un compte</a>
        @endcan
    </div>

    <div class="card table-wrap">
        @if ($bankAccounts->isEmpty())
            <p class="empty">Aucun compte bancaire n’a encore été créé.</p>
        @else
            <table>
                <thead>
                    <tr><th>Libellé</th><th>Pays</th><th>IBAN</th><th>Gestionnaire</th><th>Solde</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach ($bankAccounts as $bankAccount)
                        <tr>
                            <td><a href="{{ route('bank-accounts.show', $bankAccount) }}"><strong>{{ $bankAccount->label }}</strong></a></td>
                            <td>{{ $bankAccount->country }}</td>
                            <td>{{ $bankAccount->iban ?: '—' }}</td>
                            <td>{{ $bankAccount->manager?->name ?? '—' }}</td>
                            <td>{{ number_format($bankAccount->balance, 2, ',', ' ') }} €</td>
                            <td class="actions">
                                @can('manage bank accounts')
                                    <a class="icon-action" href="{{ route('bank-accounts.edit', $bankAccount) }}" aria-label="Modifier {{ $bankAccount->label }}" data-tooltip="Modifier" title="Modifier"><span aria-hidden="true">✎</span></a>
                                    <form method="POST" action="{{ route('bank-accounts.destroy', $bankAccount) }}" onsubmit="return confirm('Supprimer ce compte bancaire ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-action danger-action" type="submit" aria-label="Supprimer {{ $bankAccount->label }}" data-tooltip="Supprimer" title="Supprimer"><span aria-hidden="true">×</span></button>
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
