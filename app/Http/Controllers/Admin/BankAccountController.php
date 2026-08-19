<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public const COUNTRIES = [
        'FR' => 'France',
        'BE' => 'Belgique',
        'CH' => 'Suisse',
        'DE' => 'Allemagne',
        'ES' => 'Espagne',
        'IT' => 'Italie',
        'LU' => 'Luxembourg',
        'PT' => 'Portugal',
        'NL' => 'Pays-Bas',
        'GB' => 'Royaume-Uni',
    ];

    public function index(): View
    {
        return view('admin.bank-accounts.index', [
            'bankAccounts' => BankAccount::with('manager')->orderBy('label')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.bank-accounts.create', [
            'managers' => $this->managers(),
            'countries' => self::COUNTRIES,
        ]);
    }

    public function show(BankAccount $bankAccount): View
    {
        return view('admin.bank-accounts.show', [
            'bankAccount' => $bankAccount->load(['manager', 'transactions']),
        ]);
    }

    public function edit(BankAccount $bankAccount): View
    {
        return view('admin.bank-accounts.edit', [
            'bankAccount' => $bankAccount,
            'managers' => $this->managers(),
            'countries' => self::COUNTRIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        return $this->save($request, $bankAccount);
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        if ($bankAccount->transactions()->exists()) {
            return to_route('bank-accounts.index')
                ->with('error', 'Impossible de supprimer un compte auquel des écritures sont rattachées.');
        }

        $bankAccount->delete();

        return to_route('bank-accounts.index')
            ->with('success', 'Le compte bancaire a été supprimé.');
    }

    private function save(Request $request, ?BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'country' => ['required', Rule::in(array_keys(self::COUNTRIES))],
            'iban' => [
                'nullable', 'string', 'max:50',
                function (string $attribute, $value, \Closure $fail) use ($request): void {
                    $this->validateIban($value, $request->input('country'), $fail);
                },
            ],
            'initial_balance' => ['required', 'numeric'],
            'initial_balance_date' => ['required', 'date'],
            'manager_id' => ['nullable', 'exists:users,id'],
        ]);

        $validated['iban'] = $validated['iban'] ? strtoupper(str_replace(' ', '', $validated['iban'])) : null;

        if ($bankAccount) {
            $balanceShift = $validated['initial_balance'] - $bankAccount->initial_balance;
            $bankAccount->update([
                ...$validated,
                'balance' => $bankAccount->balance + $balanceShift,
            ]);
        } else {
            BankAccount::create([
                ...$validated,
                'balance' => $validated['initial_balance'],
            ]);
        }

        return to_route('bank-accounts.index')->with(
            'success',
            $bankAccount ? 'Le compte bancaire a été modifié.' : 'Le compte bancaire a été créé.',
        );
    }

    private function managers()
    {
        return User::role('gestionnaire')->orderBy('name')->get();
    }

    private function validateIban(?string $iban, ?string $country, \Closure $fail): void
    {
        if (blank($iban)) {
            return;
        }

        $normalized = strtoupper(str_replace(' ', '', $iban));

        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $normalized)) {
            $fail('L’IBAN n’est pas dans un format valide.');

            return;
        }

        if ($country && substr($normalized, 0, 2) !== $country) {
            $fail('L’IBAN ne correspond pas au pays sélectionné.');

            return;
        }

        $rearranged = substr($normalized, 4).substr($normalized, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }

        $remainder = 0;
        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        if ($remainder !== 1) {
            $fail('L’IBAN saisi est invalide (échec de la clé de contrôle).');
        }
    }
}
