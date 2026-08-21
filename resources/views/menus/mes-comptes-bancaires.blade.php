@extends('layouts.admin')

@section('title', 'Mes comptes bancaires')

@section('content')
    <div class="admin-header">
        <div>
            <p class="muted">Menu</p>
            <h1>Mes comptes bancaires</h1>
        </div>
    </div>

    <div class="card table-wrap">
        @if ($bankAccounts->isEmpty())
            <p class="empty">Aucun compte bancaire n’est rattaché à un bien qui vous est mis en gestion.</p>
        @else
            <table>
                <thead>
                    <tr><th>Libellé</th><th>Pays</th><th>IBAN</th><th>Solde</th></tr>
                </thead>
                <tbody>
                    @foreach ($bankAccounts as $bankAccount)
                        <tr>
                            <td><a href="{{ route('mes-comptes-bancaires.show', $bankAccount) }}"><strong>{{ $bankAccount->label }}</strong></a></td>
                            <td>{{ $bankAccount->country }}</td>
                            <td>{{ $bankAccount->iban ?: '—' }}</td>
                            <td>{{ number_format($bankAccount->balance, 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
