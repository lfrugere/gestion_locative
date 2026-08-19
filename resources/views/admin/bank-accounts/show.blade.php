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
                @if ($openReconciliation)
                    <a class="button" href="{{ route('bank-accounts.reconciliations.edit', [$bankAccount, $openReconciliation]) }}">Reprendre le rapprochement</a>
                @else
                    <a class="button" href="{{ route('bank-accounts.reconciliations.create', $bankAccount) }}">Nouveau rapprochement</a>
                @endif
            @endcan
        </div>
    </section>

    <div class="detail-grid">
        <div class="detail-main">
            <section class="detail-panel associated-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Mouvements</span><h2>Écritures bancaires</h2></div><span class="count-badge">{{ $transactions->count() }}</span></div>

                <form class="card" method="GET" action="{{ route('bank-accounts.show', $bankAccount) }}">
                    <div class="form-grid">
                        <div class="form-field"><label for="f_q">Libellé</label><input id="f_q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher…"></div>
                        <div class="form-field"><label for="f_date_from">Du</label><input id="f_date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
                        <div class="form-field"><label for="f_date_to">Au</label><input id="f_date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
                        <div class="form-field">
                            <label for="f_status">Statut</label>
                            <select id="f_status" name="status">
                                <option value="">Toutes</option>
                                <option value="reconciled" {{ ($filters['status'] ?? '') === 'reconciled' ? 'selected' : '' }}>Rapprochées</option>
                                <option value="unreconciled" {{ ($filters['status'] ?? '') === 'unreconciled' ? 'selected' : '' }}>Non rapprochées</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="button" type="submit">Filtrer</button>
                        @if (array_filter($filters))
                            <a class="button secondary" href="{{ route('bank-accounts.show', $bankAccount) }}">Réinitialiser</a>
                        @endif
                    </div>
                </form>

                @if ($transactions->isEmpty())
                    <p class="empty compact">Aucune écriture ne correspond à ces critères.</p>
                @else
                    <div class="card table-wrap">
                        <table>
                            <thead>
                                <tr><th>Date</th><th>Libellé</th><th style="text-align: right;">Montant</th><th>Rapprochement</th>@can('manage bank accounts')<th>Actions</th>@endcan</tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->date->format('d/m/Y') }}</td>
                                        <td>{{ $transaction->label }}</td>
                                        <td style="text-align: right;">{{ number_format($transaction->amount, 2, ',', ' ') }} €</td>
                                        <td>
                                            @if ($transaction->isLocked())
                                                <span class="status-pill status-active">Rapprochée</span>
                                            @else
                                                <span class="status-pill">Non rapprochée</span>
                                            @endif
                                        </td>
                                        @can('manage bank accounts')
                                            <td class="actions">
                                                @unless ($transaction->isLocked())
                                                    <form method="POST" action="{{ route('bank-accounts.transactions.destroy', [$bankAccount, $transaction]) }}" onsubmit="return confirm('Supprimer cette écriture ?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="icon-action danger-action" type="submit" aria-label="Supprimer l’écriture" data-tooltip="Supprimer" title="Supprimer"><span aria-hidden="true">×</span></button>
                                                    </form>
                                                @endunless
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

            <section class="detail-panel associated-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Historique</span><h2>Rapprochements</h2></div><span class="count-badge">{{ $bankAccount->reconciliations->count() }}</span></div>

                @if ($bankAccount->reconciliations->isEmpty())
                    <p class="empty compact">Aucun rapprochement n’a encore été effectué.</p>
                @else
                    <div class="card table-wrap">
                        <table>
                            <thead>
                                <tr><th>Date du relevé</th><th style="text-align: right;">Solde du relevé</th><th>Réalisé par</th><th>Statut</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($bankAccount->reconciliations as $reconciliation)
                                    <tr>
                                        <td>{{ $reconciliation->statement_date->format('d/m/Y') }}</td>
                                        <td style="text-align: right;">{{ number_format($reconciliation->statement_balance, 2, ',', ' ') }} €</td>
                                        <td>{{ $reconciliation->createdBy?->name ?? '—' }}</td>
                                        <td>
                                            @if ($reconciliation->isClosed())
                                                <span class="status-pill status-active">Clôturé le {{ $reconciliation->closed_at->format('d/m/Y') }}</span>
                                            @else
                                                <a class="status-pill" href="{{ route('bank-accounts.reconciliations.edit', [$bankAccount, $reconciliation]) }}">En cours</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <aside class="detail-aside">
            <section class="detail-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Compte</span><h2>Informations</h2></div></div>
                <p class="address-value">
                    Gestionnaire : {{ $bankAccount->manager?->name ?? 'Aucun' }}<br>
                    Solde initial : {{ number_format($bankAccount->initial_balance, 2, ',', ' ') }} € au {{ $bankAccount->initial_balance_date->format('d/m/Y') }}<br>
                    Solde actuel : {{ number_format($bankAccount->balance, 2, ',', ' ') }} €
                </p>
            </section>
        </aside>
    </div>
@endsection
