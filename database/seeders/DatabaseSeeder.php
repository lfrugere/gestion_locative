<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Les autorisations reposent désormais uniquement sur les rôles Spatie (admin,
        // gestionnaire, locataire) combinés à des Policies Laravel (app/Policies) et à
        // deux Gates (access-admin, manage-system dans AppServiceProvider) : il n'y a
        // plus de permissions Spatie à créer ni à synchroniser.
        $admin = Role::findOrCreate('admin', 'web');
        $manager = Role::findOrCreate('gestionnaire', 'web');
        Role::findOrCreate('locataire', 'web');

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
