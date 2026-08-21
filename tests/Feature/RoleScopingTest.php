<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        app(PermissionRegistrar::class)->registerPermissions(app(Gate::class));
    }

    public function test_manager_is_forbidden_from_the_admin_buildings_pages_even_by_direct_url(): void
    {
        $managedBuilding = $this->createBuilding('IMM-MANAGED');
        $property = $this->createProperty($managedBuilding, 'BIEN-MANAGED');

        $manager = $this->manager();
        $property->managers()->attach($manager);

        $this->actingAs($manager)->get(route('buildings.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('buildings.show', $managedBuilding))->assertForbidden();
        $this->actingAs($manager)->get(route('buildings.create'))->assertForbidden();
        $this->actingAs($manager)->post(route('buildings.store'), [])->assertForbidden();
        $this->actingAs($manager)->get(route('buildings.edit', $managedBuilding))->assertForbidden();
        $this->actingAs($manager)->put(route('buildings.update', $managedBuilding), [])->assertForbidden();
        $this->actingAs($manager)->delete(route('buildings.destroy', $managedBuilding))->assertForbidden();
        $this->actingAs($manager)
            ->post(route('buildings.notes.store', $managedBuilding), ['body' => 'Non autorisé'])
            ->assertForbidden();

        $this->actingAs($this->admin())->get(route('buildings.index'))->assertOk();
    }

    public function test_manager_is_forbidden_from_the_admin_tenants_pages_even_by_direct_url(): void
    {
        $building = $this->createBuilding('IMM-TEN-LOCK');
        $property = $this->createProperty($building, 'BIEN-TEN-LOCK');
        $manager = $this->manager();
        $property->managers()->attach($manager);

        $tenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'last_name' => 'Rattache',
            'first_name' => 'Bob',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $tenant->properties()->attach($property);

        $this->actingAs($manager)->get(route('tenants.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('tenants.show', $tenant))->assertForbidden();
        $this->actingAs($manager)->get(route('tenants.create'))->assertForbidden();
        $this->actingAs($manager)->post(route('tenants.store'), [])->assertForbidden();
        $this->actingAs($manager)->get(route('tenants.edit', $tenant))->assertForbidden();
        $this->actingAs($manager)->put(route('tenants.update', $tenant), [])->assertForbidden();
        $this->actingAs($manager)->delete(route('tenants.destroy', $tenant))->assertForbidden();
        $this->actingAs($manager)
            ->post(route('tenants.notes.store', $tenant), ['body' => 'Non autorisé'])
            ->assertForbidden();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);

        $this->actingAs($this->admin())->get(route('tenants.index'))->assertOk();
    }

    public function test_manager_is_forbidden_from_the_admin_bank_accounts_pages_even_by_direct_url(): void
    {
        $bankAccount = BankAccount::create([
            'label' => 'Compte scopé',
            'initial_balance' => 500,
            'initial_balance_date' => '2026-01-01',
            'balance' => 500,
        ]);
        $building = $this->createBuilding('IMM-BANK-LOCK');
        $property = $this->createProperty($building, 'BIEN-BANK-LOCK', $bankAccount->id);
        $manager = $this->manager();
        $property->managers()->attach($manager);

        // Listing, fiche et gestion du compte lui-même restent strictement admin only :
        // le gestionnaire passe par /mes-comptes-bancaires pour consulter et gérer les
        // écritures/rapprochements des comptes qu'il gère.
        $this->actingAs($manager)->get(route('bank-accounts.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('bank-accounts.show', $bankAccount))->assertForbidden();
        $this->actingAs($manager)->get(route('bank-accounts.create'))->assertForbidden();
        $this->actingAs($manager)->post(route('bank-accounts.store'), [])->assertForbidden();
        $this->actingAs($manager)->get(route('bank-accounts.edit', $bankAccount))->assertForbidden();
        $this->actingAs($manager)->put(route('bank-accounts.update', $bankAccount), [])->assertForbidden();
        $this->actingAs($manager)->delete(route('bank-accounts.destroy', $bankAccount))->assertForbidden();

        $this->actingAs($this->admin())->get(route('bank-accounts.index'))->assertOk();
    }

    public function test_manager_can_add_transactions_and_run_reconciliations_on_a_managed_bank_account(): void
    {
        $bankAccount = BankAccount::create([
            'label' => 'Compte géré',
            'initial_balance' => 500,
            'initial_balance_date' => '2026-01-01',
            'balance' => 500,
        ]);
        $unmanagedBankAccount = BankAccount::create([
            'label' => 'Compte non géré',
            'initial_balance' => 500,
            'initial_balance_date' => '2026-01-01',
            'balance' => 500,
        ]);
        $building = $this->createBuilding('IMM-BANK-MANAGE');
        $property = $this->createProperty($building, 'BIEN-BANK-MANAGE', $bankAccount->id);
        $manager = $this->manager();
        $property->managers()->attach($manager);

        $this->actingAs($manager)
            ->post(route('bank-accounts.transactions.store', $bankAccount), [
                'label' => 'Charge',
                'date' => '2026-08-20',
                'amount' => -50,
            ])
            ->assertRedirect(route('mes-comptes-bancaires.show', $bankAccount));

        $this->assertSame('450.00', $bankAccount->fresh()->balance);

        $this->actingAs($manager)
            ->get(route('bank-accounts.reconciliations.create', $bankAccount))
            ->assertOk();

        $this->actingAs($manager)
            ->post(route('bank-accounts.reconciliations.store', $bankAccount), [
                'statement_date' => '2026-08-20',
                'statement_balance' => 450,
            ])
            ->assertRedirect(route('bank-accounts.reconciliations.edit', [
                $bankAccount, $bankAccount->reconciliations()->latest()->first(),
            ]));

        // Un gestionnaire non rattaché au bien ne peut ni saisir d'écriture ni lancer de
        // rapprochement sur ce même compte.
        $this->actingAs($manager)
            ->post(route('bank-accounts.transactions.store', $unmanagedBankAccount), [
                'label' => 'Charge',
                'date' => '2026-08-20',
                'amount' => -50,
            ])
            ->assertForbidden();
        $this->actingAs($manager)
            ->get(route('bank-accounts.reconciliations.create', $unmanagedBankAccount))
            ->assertForbidden();
    }

    public function test_manager_sees_scoped_read_only_buildings_on_the_dedicated_page(): void
    {
        $managedBuilding = $this->createBuilding('IMM-MES-MANAGED');
        $otherBuilding = $this->createBuilding('IMM-MES-OTHER');
        $managedProperty = $this->createProperty($managedBuilding, 'BIEN-MES-MANAGED');

        $manager = $this->manager();
        $managedProperty->managers()->attach($manager);

        $this->actingAs($manager)
            ->get(route('mes-immeubles'))
            ->assertOk()
            ->assertSee($managedBuilding->name)
            ->assertDontSee($otherBuilding->name);

        $this->actingAs($manager)
            ->get(route('mes-immeubles.show', $managedBuilding))
            ->assertOk()
            ->assertDontSee('data-note-dialog-open="note-create-dialog"', false);

        $this->actingAs($manager)
            ->get(route('mes-immeubles.show', $otherBuilding))
            ->assertForbidden();

        // Admin does not have this dedicated page — it only makes sense for the
        // gestionnaire role, which is the only one scoped to a subset of buildings.
        $this->actingAs($this->admin())
            ->get(route('mes-immeubles'))
            ->assertForbidden();
    }

    public function test_manager_sees_scoped_read_only_tenants_on_the_dedicated_page(): void
    {
        $building = $this->createBuilding('IMM-MES-TEN');
        $managedProperty = $this->createProperty($building, 'BIEN-MES-TEN');
        $manager = $this->manager();
        $managedProperty->managers()->attach($manager);

        $attachedTenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'last_name' => 'Rattache',
            'first_name' => 'Bob',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $attachedTenant->properties()->attach($managedProperty);

        $unattachedTenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MRS,
            'last_name' => 'NonRattache',
            'first_name' => 'Alice',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)
            ->get(route('mes-locataires'))
            ->assertOk()
            ->assertSee($attachedTenant->fullName())
            ->assertDontSee($unattachedTenant->fullName());

        $this->actingAs($manager)
            ->get(route('mes-locataires.show', $attachedTenant))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('mes-locataires.show', $unattachedTenant))
            ->assertForbidden();
    }

    public function test_manager_sees_scoped_read_only_bank_accounts_on_the_dedicated_page(): void
    {
        $bankAccount = BankAccount::create([
            'label' => 'Compte scopé lecture',
            'initial_balance' => 500,
            'initial_balance_date' => '2026-01-01',
            'balance' => 500,
        ]);
        $unrelatedBankAccount = BankAccount::create([
            'label' => 'Compte non lié lecture',
            'initial_balance' => 200,
            'initial_balance_date' => '2026-01-01',
            'balance' => 200,
        ]);

        $building = $this->createBuilding('IMM-MES-BANK');
        $property = $this->createProperty($building, 'BIEN-MES-BANK', $bankAccount->id);
        $manager = $this->manager();
        $property->managers()->attach($manager);

        $this->actingAs($manager)
            ->get(route('mes-comptes-bancaires'))
            ->assertOk()
            ->assertSee($bankAccount->label)
            ->assertDontSee($unrelatedBankAccount->label);

        $this->actingAs($manager)
            ->get(route('mes-comptes-bancaires.show', $bankAccount))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('mes-comptes-bancaires.show', $unrelatedBankAccount))
            ->assertForbidden();
    }

    public function test_manager_can_add_a_note_on_a_property_room_only_if_they_manage_the_parent_property(): void
    {
        $building = $this->createBuilding('IMM-ROOM');
        $property = Property::create([
            'reference' => 'BIEN-ROOM-1',
            'name' => 'Bien colocation',
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'is_shared_accommodation' => true,
            'status' => 'active',
        ]);
        $room = $property->rooms()->create(['name' => 'Chambre 1', 'status' => 'active']);

        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('property-rooms.notes.store', [$property, $room]), ['body' => 'Non autorisé'])
            ->assertForbidden();

        $property->managers()->attach($manager);

        $this->actingAs($manager)
            ->post(route('property-rooms.notes.store', [$property, $room]), ['body' => 'Autorisé'])
            ->assertRedirect(route('property-rooms.show', [$property, $room]));

        $this->assertDatabaseHas('notes', ['body' => 'Autorisé']);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('gestionnaire');

        return $user;
    }

    private function createBuilding(string $reference): Building
    {
        $address = Address::create([
            'line1' => '1 rue du Test',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ]);

        return Building::create([
            'reference' => $reference,
            'name' => 'Immeuble '.$reference,
            'address_id' => $address->id,
        ]);
    }

    private function createProperty(Building $building, string $reference, ?int $bankAccountId = null): Property
    {
        return Property::create([
            'reference' => $reference,
            'name' => 'Bien '.$reference,
            'type' => Property::TYPE_APARTMENT,
            'building_id' => $building->id,
            'bank_account_id' => $bankAccountId,
            'status' => 'active',
        ]);
    }
}
