<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankReconciliationController extends Controller
{
    public function create(BankAccount $bankAccount): View
    {
        $this->authorize('manageTransactions', $bankAccount);

        $lastClosed = $this->lastClosedReconciliation($bankAccount);
        $isFirstReconciliation = $lastClosed === null;

        return view('admin.bank-accounts.reconciliations.create', [
            'bankAccount' => $bankAccount,
            'openingBalance' => $lastClosed?->statement_balance ?? $bankAccount->initial_balance,
            'openingDate' => $lastClosed?->statement_date ?? $bankAccount->initial_balance_date,
            'isFirstReconciliation' => $isFirstReconciliation,
            'suggestedStatementBalance' => $bankAccount->balance,
        ]);
    }

    public function store(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('manageTransactions', $bankAccount);

        if ($bankAccount->reconciliations()->whereNull('closed_at')->exists()) {
            return to_route($this->showRouteName(), $bankAccount)
                ->with('error', 'Un rapprochement est déjà en cours pour ce compte. Clôturez-le ou supprimez-le avant d’en démarrer un nouveau.');
        }

        $lastClosed = $this->lastClosedReconciliation($bankAccount);

        $validated = $request->validate([
            'statement_date' => [
                'required',
                'date',
                'after_or_equal:'.($lastClosed?->statement_date ?? $bankAccount->initial_balance_date)->format('Y-m-d'),
            ],
            'statement_balance' => ['required', 'numeric'],
        ]);

        $reconciliation = $bankAccount->reconciliations()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return to_route('bank-accounts.reconciliations.edit', [$bankAccount, $reconciliation]);
    }

    public function edit(BankAccount $bankAccount, BankReconciliation $reconciliation): View
    {
        $this->authorizeReconciliation($bankAccount, $reconciliation);

        $lastClosed = $this->lastClosedReconciliation($bankAccount, $reconciliation);
        $openingBalance = $lastClosed?->statement_balance ?? $bankAccount->initial_balance;

        $transactions = $this->eligibleTransactions($bankAccount, $reconciliation)->get();

        $pointedTotal = $transactions
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->sum('amount');

        return view('admin.bank-accounts.reconciliations.edit', [
            'bankAccount' => $bankAccount,
            'reconciliation' => $reconciliation,
            'transactions' => $transactions,
            'pointedTotal' => $pointedTotal,
            'openingBalance' => $openingBalance,
            'expectedTotal' => $reconciliation->statement_balance - $openingBalance,
        ]);
    }

    public function update(Request $request, BankAccount $bankAccount, BankReconciliation $reconciliation): RedirectResponse
    {
        $this->authorizeReconciliation($bankAccount, $reconciliation);

        $selectedIds = collect($request->input('transactions', []))->map(fn ($id) => (int) $id);

        $eligibleIds = $this->eligibleTransactions($bankAccount, $reconciliation)->pluck('id');

        DB::transaction(function () use ($eligibleIds, $selectedIds, $reconciliation): void {
            BankTransaction::whereIn('id', $eligibleIds->intersect($selectedIds))
                ->update(['bank_reconciliation_id' => $reconciliation->id]);

            BankTransaction::whereIn('id', $eligibleIds->diff($selectedIds))
                ->where('bank_reconciliation_id', $reconciliation->id)
                ->update(['bank_reconciliation_id' => null]);
        });

        return to_route('bank-accounts.reconciliations.edit', [$bankAccount, $reconciliation])
            ->with('success', 'Le pointage a été mis à jour.');
    }

    public function close(BankAccount $bankAccount, BankReconciliation $reconciliation): RedirectResponse
    {
        $this->authorizeReconciliation($bankAccount, $reconciliation);

        $lastClosed = $this->lastClosedReconciliation($bankAccount, $reconciliation);
        $openingBalance = $lastClosed?->statement_balance ?? $bankAccount->initial_balance;

        $pointedTotal = $reconciliation->transactions()->sum('amount');
        $expectedTotal = $reconciliation->statement_balance - $openingBalance;

        if (bccomp(number_format($pointedTotal, 2, '.', ''), number_format($expectedTotal, 2, '.', ''), 2) !== 0) {
            return to_route('bank-accounts.reconciliations.edit', [$bankAccount, $reconciliation])
                ->with('error', 'Le solde pointé ne correspond pas au solde du relevé. Impossible de clôturer.');
        }

        $reconciliation->update(['closed_at' => now()]);

        return to_route($this->showRouteName(), $bankAccount)
            ->with('success', 'Le rapprochement a été clôturé.');
    }

    public function destroy(BankAccount $bankAccount, BankReconciliation $reconciliation): RedirectResponse
    {
        $this->authorizeReconciliation($bankAccount, $reconciliation);

        if ($reconciliation->isClosed()) {
            abort(403);
        }

        DB::transaction(function () use ($reconciliation): void {
            $reconciliation->transactions()->update(['bank_reconciliation_id' => null]);
            $reconciliation->delete();
        });

        return to_route($this->showRouteName(), $bankAccount)
            ->with('success', 'Le rapprochement a été supprimé.');
    }

    private function authorizeReconciliation(BankAccount $bankAccount, BankReconciliation $reconciliation): void
    {
        $this->authorize('manageTransactions', $bankAccount);

        if ($reconciliation->bank_account_id !== $bankAccount->id) {
            abort(404);
        }

        if ($reconciliation->isClosed()) {
            abort(403);
        }
    }

    private function showRouteName(): string
    {
        return auth()->user()->hasRole('admin') ? 'bank-accounts.show' : 'mes-comptes-bancaires.show';
    }

    private function eligibleTransactions(BankAccount $bankAccount, BankReconciliation $reconciliation)
    {
        return $bankAccount->transactions()
            ->where('date', '<=', $reconciliation->statement_date)
            ->where(function ($query) use ($reconciliation) {
                $query->whereNull('bank_reconciliation_id')
                    ->orWhere('bank_reconciliation_id', $reconciliation->id);
            });
    }

    private function lastClosedReconciliation(BankAccount $bankAccount, ?BankReconciliation $before = null): ?BankReconciliation
    {
        return $bankAccount->reconciliations()
            ->whereNotNull('closed_at')
            ->when($before, fn ($query) => $query->where('id', '!=', $before->id))
            ->orderByDesc('statement_date')
            ->orderByDesc('id')
            ->first();
    }
}
