<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PropertyInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        app(PermissionRegistrar::class)->registerPermissions(app(Gate::class));
    }

    public function test_admin_creating_an_invoice_adds_an_expense_to_the_linked_bank_account(): void
    {
        $bankAccount = BankAccount::create([
            'label' => 'Compte principal',
            'initial_balance' => 1000,
            'initial_balance_date' => '2026-01-01',
            'balance' => 1000,
        ]);
        $property = $this->createProperty($bankAccount->id);

        $this->actingAs($this->admin())
            ->post(route('properties.invoices.store', $property), [
                'number' => 'F-2026-001',
                'label' => 'Réparation chaudière',
                'amount' => 150.50,
                'date' => '2026-08-20',
            ])
            ->assertRedirect();

        $property->refresh();
        $invoice = $property->invoices()->first();

        $this->assertNotNull($invoice);
        $this->assertSame('150.50', $invoice->amount);
        $this->assertNotNull($invoice->bank_transaction_id);

        $transaction = $invoice->bankTransaction;
        $this->assertSame('-150.50', $transaction->amount);
        $this->assertSame($bankAccount->id, $transaction->bank_account_id);

        $this->assertSame('849.50', $bankAccount->fresh()->balance);
    }

    public function test_invoice_without_linked_bank_account_does_not_create_a_transaction(): void
    {
        $property = $this->createProperty(null);

        $this->actingAs($this->admin())
            ->post(route('properties.invoices.store', $property), [
                'number' => 'F-2026-002',
                'label' => 'Petits travaux',
                'amount' => 80,
                'date' => '2026-08-20',
            ])
            ->assertRedirect();

        $invoice = $property->invoices()->first();

        $this->assertNotNull($invoice);
        $this->assertNull($invoice->bank_transaction_id);
    }

    public function test_deleting_an_invoice_removes_its_bank_transaction_and_restores_the_balance(): void
    {
        $bankAccount = BankAccount::create([
            'label' => 'Compte principal',
            'initial_balance' => 1000,
            'initial_balance_date' => '2026-01-01',
            'balance' => 1000,
        ]);
        $property = $this->createProperty($bankAccount->id);

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('properties.invoices.store', $property), [
            'number' => 'F-2026-003',
            'label' => 'Nettoyage',
            'amount' => 60,
            'date' => '2026-08-20',
        ]);

        $invoice = $property->invoices()->first();

        $this->actingAs($admin)
            ->delete(route('properties.invoices.destroy', [$property, $invoice]))
            ->assertRedirect();

        $this->assertNull($property->invoices()->find($invoice->id));
        $this->assertDatabaseMissing('bank_transactions', ['id' => $invoice->bank_transaction_id]);
        $this->assertSame('1000.00', $bankAccount->fresh()->balance);
    }

    public function test_manager_without_invoice_permission_cannot_create_an_invoice(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('gestionnaire');
        $property = $this->createProperty(null);

        $this->actingAs($manager)
            ->post(route('properties.invoices.store', $property), [
                'number' => 'F-2026-004',
                'label' => 'Test',
                'amount' => 10,
                'date' => '2026-08-20',
            ])
            ->assertForbidden();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function createProperty(?int $bankAccountId): Property
    {
        $address = Address::create([
            'line1' => '1 rue du Test',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ]);

        $building = Building::create([
            'reference' => 'IMMEUBLE-TEST',
            'name' => 'Immeuble test',
            'address_id' => $address->id,
        ]);

        return Property::create([
            'reference' => 'BIEN-TEST',
            'name' => 'Bien test',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'bank_account_id' => $bankAccountId,
            'status' => 'active',
        ]);
    }
}
