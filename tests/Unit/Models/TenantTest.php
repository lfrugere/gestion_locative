<?php

namespace Tests\Unit\Models;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_key_is_generated_on_creation(): void
    {
        $tenant = Tenant::create([
            'civility' => Tenant::CIVILITY_MR,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'status' => Tenant::STATUS_CANDIDATE,
        ]);

        $this->assertNotEmpty($tenant->storage_key);
    }

    public function test_storage_key_is_not_overwritten_when_already_set(): void
    {
        $tenant = new Tenant([
            'civility' => Tenant::CIVILITY_MR,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'status' => Tenant::STATUS_CANDIDATE,
        ]);
        $tenant->storage_key = 'fixed-key';
        $tenant->save();

        $this->assertSame('fixed-key', $tenant->storage_key);
    }

    public function test_full_name_trims_and_joins_first_and_last_name(): void
    {
        $tenant = new Tenant(['first_name' => 'Jean', 'last_name' => 'Dupont']);
        $this->assertSame('Jean Dupont', $tenant->fullName());

        $onlyFirstName = new Tenant(['first_name' => 'Jean', 'last_name' => '']);
        $this->assertSame('Jean', $onlyFirstName->fullName());

        $onlyLastName = new Tenant(['first_name' => '', 'last_name' => 'Dupont']);
        $this->assertSame('Dupont', $onlyLastName->fullName());
    }

    public function test_civility_label_falls_back_to_the_raw_value_for_an_unknown_civility(): void
    {
        $tenant = new Tenant(['civility' => Tenant::CIVILITY_MRS]);
        $this->assertSame('Madame', $tenant->civilityLabel());

        $unknown = new Tenant(['civility' => 'unknown']);
        $this->assertSame('unknown', $unknown->civilityLabel());
    }

    public function test_status_label_falls_back_to_the_raw_value_for_an_unknown_status(): void
    {
        $tenant = new Tenant(['status' => Tenant::STATUS_ACTIVE]);
        $this->assertSame('Actif', $tenant->statusLabel());

        $unknown = new Tenant(['status' => 'unknown']);
        $this->assertSame('unknown', $unknown->statusLabel());
    }
}
