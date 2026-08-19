@extends('layouts.admin')

@section('title', 'Nouveau rapprochement · '.$bankAccount->label)

@section('content')
    <section class="detail-hero">
        <div class="detail-hero-copy">
            <a class="back-link" href="{{ route('bank-accounts.show', $bankAccount) }}">← {{ $bankAccount->label }}</a>
            <div class="eyebrow">Rapprochement bancaire</div>
            <h1>Nouveau rapprochement</h1>
            <p class="detail-lead">Saisissez la date et le solde de clôture indiqués sur votre relevé bancaire.</p>
        </div>
    </section>

    <div class="detail-grid">
        <div class="detail-main">
            <section class="detail-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Point de départ</span><h2>Solde d’ouverture</h2></div></div>
                <p class="address-value">
                    Solde d’ouverture retenu : {{ number_format($openingBalance, 2, ',', ' ') }} € au {{ $openingDate->format('d/m/Y') }}<br>
                    @if ($isFirstReconciliation)
                        <span class="hint">C’est le premier rapprochement de ce compte : ce solde correspond au solde initial du compte.</span>
                    @else
                        <span class="hint">Ce solde correspond au dernier rapprochement clôturé.</span>
                    @endif
                </p>
            </section>

            <section class="detail-panel">
                <form class="card" method="POST" action="{{ route('bank-accounts.reconciliations.store', $bankAccount) }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="statement_date">Date du relevé</label>
                            <input id="statement_date" type="date" name="statement_date" min="{{ $openingDate->format('Y-m-d') }}" value="{{ old('statement_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="form-field">
                            <label for="statement_balance">Solde de clôture du relevé</label>
                            <input id="statement_balance" type="number" step="0.01" name="statement_balance" value="{{ old('statement_balance', $suggestedStatementBalance) }}" required>
                        </div>
                    </div>
                    @if ($suggestedStatementBalance !== null)
                        <p class="hint">Il s’agit du premier rapprochement de ce compte : nous avons pré-rempli le solde de clôture avec le solde actuel du compte ({{ number_format($suggestedStatementBalance, 2, ',', ' ') }} €). Corrigez-le si le solde de votre relevé bancaire diffère.</p>
                    @endif
                    <div class="form-actions"><button class="button" type="submit">Commencer le pointage</button></div>
                </form>
            </section>
        </div>
    </div>
@endsection
