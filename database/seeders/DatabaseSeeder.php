<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);
        $permissionRegistrar->forgetCachedPermissions();

        $permissions = [
            'access admin',
            'view buildings',
            'manage buildings',
            'view properties',
            'manage properties',
            'view tenants',
            'manage tenants',
            'view bank accounts',
            'manage bank accounts',
            'manage invoices',
            'manage notes',
            'manage system',
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $permissionRegistrar->forgetCachedPermissions();

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($permissions);

        $manager = Role::findOrCreate('gestionnaire', 'web');
        $manager->syncPermissions([
            'access admin',
            'view buildings',
            'view properties',
            'view tenants',
            'view bank accounts',
            'manage notes',
            'manage invoices',
        ]);

        $tenantRole = Role::findOrCreate('locataire', 'web');
        $tenantRole->syncPermissions([
            'access admin',
        ]);

        $permissionRegistrar->forgetCachedPermissions();

        $this->seedUser($admin, env('ADMIN_EMAIL'), env('ADMIN_PASSWORD'), env('ADMIN_NAME', 'Administrateur'));
        $this->seedUser($manager, env('MANAGER_EMAIL'), env('MANAGER_PASSWORD'), env('MANAGER_NAME', 'Gestionnaire'));
    }

    private function seedUser(Role $role, ?string $email, ?string $password, string $name): void
    {
        if (blank($email) || blank($password)) {
            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ],
        );

        $user->syncRoles([$role]);
    }
}
