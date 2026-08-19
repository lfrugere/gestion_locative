<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankTransactionController extends Controller
{
    public function store(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric'],
        ]);

        DB::transaction(function () use ($validated, $bankAccount): void {
            $bankAccount->transactions()->create($validated);
            $bankAccount->increment('balance', $validated['amount']);
        });

        return to_route('bank-accounts.show', $bankAccount)
            ->with('success', 'L’écriture a été ajoutée.');
    }

    public function destroy(BankAccount $bankAccount, BankTransaction $transaction): RedirectResponse
    {
        DB::transaction(function () use ($bankAccount, $transaction): void {
            $bankAccount->decrement('balance', $transaction->amount);
            $transaction->delete();
        });

        return to_route('bank-accounts.show', $bankAccount)
            ->with('success', 'L’écriture a été supprimée.');
    }
}
