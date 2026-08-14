<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        ]);

        Role::findOrCreate('locataire', 'web');

        $permissionRegistrar->forgetCachedPermissions();

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            return;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrateur'),
                'password' => Hash::make($password),
            ],
        );

        $user->syncRoles($admin);
    }
}
