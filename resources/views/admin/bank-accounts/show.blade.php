@extends('layouts.admin')

@section('title', $bankAccount->label)

@section('content')
    <section class="detail-hero">
        <div class="detail-hero-copy">
            <a class="back-link" href="{{ route('bank-accounts.index') }}">← Tous les comptes bancaires</a>
            <div class="eyebrow">Compte bancaire</div>
            <h1>{{ $bankAccount->label }}</h1>
            <p class="detail-lead">{{ $bankAccount->country }} · {{ $bankAccount->iban ?: 'Aucun IBAN renseigné' }}</p>
        </div>
        <div class="detail-hero-actions">
            <span class="status-pill status-active">Solde : {{ number_format($bankAccount->balance, 2, ',', ' ') }} €</span>
            @can('manage bank accounts')
                <a class="button secondary" href="{{ route('bank-accounts.edit', $bankAccount) }}">Modifier</a>
            @endcan
        </div>
    </section>

    <div class="detail-grid">
        <div class="detail-main">
            <section class="detail-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Compte</span><h2>Informations</h2></div></div>
                <p class="address-value">
                    Gestionnaire : {{ $bankAccount->manager?->name ?? 'Aucun' }}<br>
                    Solde initial : {{ number_format($bankAccount->initial_balance, 2, ',', ' ') }} € au {{ $bankAccount->initial_balance_date->format('d/m/Y') }}<br>
                    Solde actuel : {{ number_format($bankAccount->balance, 2, ',', ' ') }} €
                </p>
            </section>

            <section class="detail-panel associated-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Mouvements</span><h2>Écritures bancaires</h2></div><span class="count-badge">{{ $bankAccount->transactions->count() }}</span></div>

                @if ($bankAccount->transactions->isEmpty())
                    <p class="empty compact">Aucune écriture n’a encore été enregistrée.</p>
                @else
                    <div class="card table-wrap">
                        <table>
                            <thead>
                                <tr><th>Date</th><th>Libellé</th><th>Montant</th>@can('manage bank accounts')<th>Actions</th>@endcan</tr>
                            </thead>
                            <tbody>
                                @foreach ($bankAccount->transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->date->format('d/m/Y') }}</td>
                                        <td>{{ $transaction->label }}</td>
                                        <td>{{ number_format($transaction->amount, 2, ',', ' ') }} €</td>
                                        @can('manage bank accounts')
                                            <td class="actions">
                                                <form method="POST" action="{{ route('bank-accounts.transactions.destroy', [$bankAccount, $transaction]) }}" onsubmit="return confirm('Supprimer cette écriture ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="icon-action danger-action" type="submit" aria-label="Supprimer l’écriture" data-tooltip="Supprimer" title="Supprimer"><span aria-hidden="true">×</span></button>
                                                </form>
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @can('manage bank accounts')
                    <form class="card" method="POST" action="{{ route('bank-accounts.transactions.store', $bankAccount) }}">
                        @csrf
                        <div class="form-grid">
                            <div class="form-field"><label for="tx_label">Libellé</label><input id="tx_label" name="label" required></div>
                            <div class="form-field"><label for="tx_date">Date</label><input id="tx_date" type="date" name="date" value="{{ now()->format('Y-m-d') }}" required></div>
                            <div class="form-field"><label for="tx_amount">Montant</label><input id="tx_amount" type="number" step="0.01" name="amount" placeholder="-100.00 ou 100.00" required></div>
                        </div>
                        <div class="form-actions"><button class="button" type="submit">Ajouter l’écriture</button></div>
                    </form>
                @endcan
            </section>
        </div>
    </div>
@endsection
