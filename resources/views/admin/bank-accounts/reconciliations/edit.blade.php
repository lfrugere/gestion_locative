@extends('layouts.admin')

@section('title', 'Pointage · '.$bankAccount->label)

@section('content')
    <section class="detail-hero">
        <div class="detail-hero-copy">
            <a class="back-link" href="{{ route('bank-accounts.show', $bankAccount) }}">← {{ $bankAccount->label }}</a>
            <div class="eyebrow">Rapprochement bancaire</div>
            <h1>Relevé du {{ $reconciliation->statement_date->format('d/m/Y') }}</h1>
            <p class="detail-lead">Cochez les écritures qui figurent sur votre relevé bancaire, puis enregistrez. Clôturez une fois l’écart à zéro.</p>
        </div>
        <div class="detail-hero-actions">
            <form method="POST" action="{{ route('bank-accounts.reconciliations.destroy', [$bankAccount, $reconciliation]) }}" onsubmit="return confirm('Abandonner ce rapprochement ?')">
                @csrf
                @method('DELETE')
                <button class="button secondary" type="submit">Abandonner</button>
            </form>
        </div>
    </section>

    <div class="detail-grid">
        <div class="detail-main">
            @php
                $gap = $expectedTotal - $pointedTotal;
                $balanced = bccomp((string) $gap, '0.00', 2) === 0;
            @endphp

            <section class="detail-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Solde</span><h2>État du pointage</h2></div></div>
                <div class="card table-wrap">
                    <table>
                        <tbody>
                            <tr><td>Solde d’ouverture</td><td style="text-align: right;">{{ number_format($openingBalance, 2, ',', ' ') }} €</td></tr>
                            <tr><td>Solde du relevé (à atteindre)</td><td style="text-align: right;">{{ number_format($reconciliation->statement_balance, 2, ',', ' ') }} €</td></tr>
                            <tr><td>Mouvement attendu</td><td style="text-align: right;">{{ number_format($expectedTotal, 2, ',', ' ') }} €</td></tr>
                            <tr><td>Total pointé ci-dessous</td><td style="text-align: right;">{{ number_format($pointedTotal, 2, ',', ' ') }} €</td></tr>
                            <tr><td><strong>Écart</strong></td><td style="text-align: right;"><strong>{{ number_format($gap, 2, ',', ' ') }} €</strong></td></tr>
                        </tbody>
                    </table>
                </div>

                @if ($balanced)
                    <form method="POST" action="{{ route('bank-accounts.reconciliations.close', [$bankAccount, $reconciliation]) }}">
                        @csrf
                        <div class="form-actions"><button class="button" type="submit">Clôturer le rapprochement</button></div>
                    </form>
                @else
                    <p class="empty compact">L’écart doit être nul pour pouvoir clôturer le rapprochement.</p>
                @endif
            </section>

            <section class="detail-panel associated-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Écritures</span><h2>À pointer</h2></div></div>

                @if ($transactions->isEmpty())
                    <p class="empty compact">Aucune écriture disponible pour ce relevé.</p>
                @else
                    <form method="POST" action="{{ route('bank-accounts.reconciliations.update', [$bankAccount, $reconciliation]) }}">
                        @csrf
                        @method('PATCH')
                        <div class="card table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Date</th>
                                        <th>Libellé</th>
                                        <th style="text-align: right;">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $transaction)
                                        @php $pointed = $transaction->bank_reconciliation_id === $reconciliation->id; @endphp
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="transactions[]" value="{{ $transaction->id }}" id="tx_{{ $transaction->id }}" {{ $pointed ? 'checked' : '' }}>
                                            </td>
                                            <td><label for="tx_{{ $transaction->id }}">{{ $transaction->date->format('d/m/Y') }}</label></td>
                                            <td><label for="tx_{{ $transaction->id }}">{{ $transaction->label }}</label></td>
                                            <td style="text-align: right;"><label for="tx_{{ $transaction->id }}">{{ number_format($transaction->amount, 2, ',', ' ') }} €</label></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="form-actions"><button class="button" type="submit">Mettre à jour le pointage</button></div>
                    </form>
                @endif
            </section>
        </div>
    </div>
@endsection
