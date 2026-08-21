<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private const ENV_KEYS = [
        'ADMIN_NAME',
        'ADMIN_EMAIL',
        'ADMIN_PASSWORD',
        'MANAGER_NAME',
        'MANAGER_EMAIL',
        'MANAGER_PASSWORD',
    ];

    public function test_seeded_admin_account_only_has_the_admin_role(): void
    {
        $this->withEnv([
            'ADMIN_NAME' => 'Administrateur',
            'ADMIN_EMAIL' => 'admin@example.com',
            'ADMIN_PASSWORD' => 'secret-password',
        ], function (): void {
            $this->seed(DatabaseSeeder::class);
        });

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse($admin->hasRole('gestionnaire'));
        $this->assertCount(1, $admin->roles);
    }

    public function test_seeded_manager_account_only_has_the_gestionnaire_role(): void
    {
        $this->withEnv([
            'MANAGER_NAME' => 'Gestionnaire',
            'MANAGER_EMAIL' => 'gestionnaire@example.com',
            'MANAGER_PASSWORD' => 'secret-password',
        ], function (): void {
            $this->seed(DatabaseSeeder::class);
        });

        $manager = User::where('email', 'gestionnaire@example.com')->firstOrFail();

        $this->assertTrue($manager->hasRole('gestionnaire'));
        $this->assertFalse($manager->hasRole('admin'));
        $this->assertCount(1, $manager->roles);
    }

    public function test_seeding_without_admin_or_manager_env_vars_creates_no_user(): void
    {
        $this->withEnv([], function (): void {
            $this->seed(DatabaseSeeder::class);
        });

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Seeds the DatabaseSeeder's env() reads, overriding whatever is defined in the
     * developer's local .env so these tests behave the same in every environment.
     *
     * @param  array<string, string>  $variables
     */
    private function withEnv(array $variables, callable $callback): void
    {
        $original = [];

        foreach (self::ENV_KEYS as $key) {
            $original[$key] = $_ENV[$key] ?? null;
            $value = $variables[$key] ?? null;

            if ($value === null) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        try {
            $callback();
        } finally {
            foreach (self::ENV_KEYS as $key) {
                if ($original[$key] === null) {
                    unset($_ENV[$key], $_SERVER[$key]);
                    putenv($key);
                } else {
                    $_ENV[$key] = $original[$key];
                    $_SERVER[$key] = $original[$key];
                    putenv("{$key}={$original[$key]}");
                }
            }
        }
    }
}
