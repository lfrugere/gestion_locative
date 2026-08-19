<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        app(PermissionRegistrar::class)->registerPermissions(app(Gate::class));
    }

    public function test_guest_cannot_manage_reconciliations(): void
    {
        $bankAccount = $this->createBankAccount();

        $this->get(route('bank-accounts.reconciliations.create', $bankAccount))
            ->assertRedirect('/login');
    }

    public function test_manager_without_manage_permission_is_forbidden(): void
    {
        $bankAccount = $this->createBankAccount();
        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');

        $this->actingAs($manager)
            ->get(route('bank-accounts.reconciliations.create', $bankAccount))
            ->assertForbidden();
    }

    public function test_first_reconciliation_suggests_the_current_balance_and_uses_the_initial_balance_as_opening(): void
    {
        $bankAccount = $this->createBankAccount(initialBalance: 1000, balance: 1250);

        $response = $this->actingAs($this->admin())
            ->get(route('bank-accounts.reconciliations.create', $bankAccount));

        $response->assertOk()
            ->assertSee('1 000,00')
            ->assertSee('1250');
    }

    public function test_store_rejects_a_new_reconciliation_when_one_is_already_open(): void
    {
        $bankAccount = $this->createBankAccount();
        $bankAccount->reconciliations()->create([
            'statement_date' => '2026-06-01',
            'statement_balance' => 1000,
        ]);

        $this->actingAs($this->admin())
            ->post(route('bank-accounts.reconciliations.store', $bankAccount), [
                'statement_date' => '2026-07-01',
                'statement_balance' => 1200,
            ])
            ->assertRedirect(route('bank-accounts.show', $bankAccount))
            ->assertSessionHas('error');

        $this->assertSame(1, $bankAccount->reconciliations()->count());
    }

    public function test_store_rejects_a_statement_date_before_the_opening_date(): void
    {
        $bankAccount = $this->createBankAccount(initialBalanceDate: '2026-06-01');

        $this->actingAs($this->admin())
            ->post(route('bank-accounts.reconciliations.store', $bankAccount), [
                'statement_date' => '2026-05-01',
                'statement_balance' => 1000,
            ])
            ->assertSessionHasErrors('statement_date');
    }

    public function test_update_points_and_unpoints_selected_transactions(): void
    {
        $bankAccount = $this->createBankAccount(initialBalance: 1000, initialBalanceDate: '2026-06-01');
        $rent = $this->createTransaction($bankAccount, 'Loyer', '2026-06-05', 500);
        $fees = $this->createTransaction($bankAccount, 'Frais', '2026-06-06', -20);

        $reconciliation = $bankAccount->reconciliations()->create([
            'statement_date' => '2026-06-30',
            'statement_balance' => 1480,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('bank-accounts.reconciliations.update', [$bankAccount, $reconciliation]), [
                'transactions' => [$rent->id, $fees->id],
            ])
            ->assertRedirect(route('bank-accounts.reconciliations.edit', [$bankAccount, $reconciliation]));

        $this->assertSame($reconciliation->id, $rent->fresh()->bank_reconciliation_id);
        $this->assertSame($reconciliation->id, $fees->fresh()->bank_reconciliation_id);

        $this->actingAs($this->admin())
            ->patch(route('bank-accounts.reconciliations.update', [$bankAccount, $reconciliation]), [
                'transactions' => [$rent->id],
            ]);

        $this->assertSame($reconciliation->id, $rent->fresh()->bank_reconciliation_id);
        $this->assertNull($fees->fresh()->bank_reconciliation_id);
    }

    public function test_update_ignores_transactions_dated_after_the_statement_date(): void
    {
        $bankAccount = $this->createBankAccount(initialBalanceDate: '2026-06-01');
        $future = $this->createTransaction($bankAccount, 'Loyer futur', '2026-07-15', 500);

        $reconciliation = $bankAccount->reconciliations()->create([
            'statement_date' => '2026-06-30',
            'statement_balance' => 1000,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('bank-accounts.reconciliations.update', [$bankAccount, $reconciliation]), [
                'transactions' => [$future->id],
            ]);

        $this->assertNull($future->fresh()->bank_reconciliation_id);
    }

    public function test_close_fails_when_the_gap_is_not_zero(): void
    {
        $bankAccount = $this->createBankAccount(initialBalance: 1000, initialBalanceDate: '2026-06-01');
        $reconciliation = $bankAccount->reconciliations()->create([
            'statement_date' => '2026-06-30',
            'statement_balance' => 1200,
        ]);

        $this->actingAs($this->admin())
            ->post(route('bank-accounts.reconciliations.close', [$bankAccount, $reconciliation]))
            ->assertRedirect(route('bank-accounts.reconciliations.edit', [$bankAccount, $reconciliation]))
            ->assertSessionHas('error');

        $this->assertNull($reconciliation->fresh()->closed_at);
    }

    public function test_close_succeeds_and_locks_the_pointed_transactions_when_the_gap_is_zero(): void
    {
        $bankAccount = $this->createBankAccount(initialBalance: 1000, initialBalanceDate: '2026-06-01');
        $rent = $this->createTransaction($bankAccount, 'Loyer', '2026-06-05', 500);

        $reconciliation = $bankAccount->reconciliations()->create([
            'statement_date' => '2026-06-30',
            'statement_balance' => 1500,
        ]);
        $rent->update(['bank_reconciliation_id' => $reconciliation->id]);

        $this->actingAs($this->admin())
            ->post(route('bank-accounts.reconciliations.close', [$bankAccount, $reconciliation]))
            ->assertRedirect(route('bank-accounts.show', $bankAccount))
            ->assertSessionHas('success');

        $this->assertNotNull($reconciliation->fresh()->closed_at);
        $this->assertTrue($rent->fresh()->isLocked());
    }

    public function test_second_reconciliation_opening_balance_is_the_last_closed_statement_balance(): void
    {
        $bankAccount = $this->createBankAccount(initialBalance: 1000, initialBalanceDate: '2026-06-01');
        $bankAccount->reconciliations()->create([
            'statement_date' => '2026-06-30',
            'statement_balance' => 1500,
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('bank-accounts.reconciliations.create', $bankAccount));

        $response->assertOk()->assertSee('1 500,00');
    }

    public function test_locked_transaction_cannot_be_deleted(): void
    {
        $bankAccount = $this->createBankAccount(initialBalance: 1000, initialBalanceDate: '2026-06-01');
        $rent = $this->createTransaction($bankAccount, 'Loyer', '2026-06-05', 500);

        $reconciliation = $bankAccount->reconciliations()->create([
            'statement_date' => '2026-06-30',
            'statement_balance' => 1500,
            'closed_at' => now(),
        ]);
        $rent->update(['bank_reconciliation_id' => $reconciliation->id]);

        $this->actingAs($this->admin())
            ->delete(route('bank-accounts.transactions.destroy', [$bankAccount, $rent]))
            ->assertRedirect(route('bank-accounts.show', $bankAccount))
            ->assertSessionHas('error');

        $this->assertNotNull($rent->fresh());
    }

    public function test_destroy_unlinks_transactions_and_removes_an_open_reconciliation(): void
    {
        $bankAccount = $this->createBankAccount(initialBalance: 1000, initialBalanceDate: '2026-06-01');
        $rent = $this->createTransaction($bankAccount, 'Loyer', '2026-06-05', 500);

        $reconciliation = $bankAccount->reconciliations()->create([
            'statement_date' => '2026-06-30',
            'statement_balance' => 1500,
        ]);
        $rent->update(['bank_reconciliation_id' => $reconciliation->id]);

        $this->actingAs($this->admin())
            ->delete(route('bank-accounts.reconciliations.destroy', [$bankAccount, $reconciliation]))
            ->assertRedirect(route('bank-accounts.show', $bankAccount))
            ->assertSessionHas('success');

        $this->assertNull(BankReconciliation::find($reconciliation->id));
        $this->assertNull($rent->fresh()->bank_reconciliation_id);
    }

    public function test_closed_reconciliation_cannot_be_destroyed(): void
    {
        $bankAccount = $this->createBankAccount(initialBalance: 1000, initialBalanceDate: '2026-06-01');
        $reconciliation = $bankAccount->reconciliations()->create([
            'statement_date' => '2026-06-30',
            'statement_balance' => 1000,
            'closed_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->delete(route('bank-accounts.reconciliations.destroy', [$bankAccount, $reconciliation]))
            ->assertForbidden();

        $this->assertNotNull(BankReconciliation::find($reconciliation->id));
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function createBankAccount(
        float $initialBalance = 1000,
        ?string $initialBalanceDate = '2026-06-01',
        ?float $balance = null,
    ): BankAccount {
        return BankAccount::create([
            'label' => 'Compte test',
            'country' => 'FR',
            'iban' => null,
            'initial_balance' => $initialBalance,
            'initial_balance_date' => $initialBalanceDate,
            'balance' => $balance ?? $initialBalance,
        ]);
    }

    private function createTransaction(BankAccount $bankAccount, string $label, string $date, float $amount): BankTransaction
    {
        return $bankAccount->transactions()->create([
            'label' => $label,
            'date' => $date,
            'amount' => $amount,
        ]);
    }
}
