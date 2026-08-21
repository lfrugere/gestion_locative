<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 'access admin' n'est pas rattaché à un modèle Eloquent : tout utilisateur ayant
        // l'un des trois rôles applicatifs accède à l'espace d'administration.
        Gate::define('access-admin', fn (User $user): bool => $user->hasRole('admin')
            || $user->hasRole('gestionnaire')
            || $user->hasRole('locataire'));

        // 'manage system' n'est pas rattaché à un modèle Eloquent : réservé à l'admin.
        Gate::define('manage-system', fn (User $user): bool => $user->hasRole('admin'));
    }
}
