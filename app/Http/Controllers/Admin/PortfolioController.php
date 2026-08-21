<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function myProperties(): View
    {
        $user = auth()->user();

        $properties = $user->hasRole('admin')
            ? Property::with(['building', 'address'])->orderBy('reference')->get()
            : $user->managedProperties()->with(['building', 'address'])->orderBy('reference')->get();

        return view('menus.mes-biens', ['properties' => $properties]);
    }

    public function showProperty(Property $property): View
    {
        $user = auth()->user();

        abort_unless(
            $user->hasRole('admin') || $property->isManagedBy($user),
            403,
        );

        return view('menus.mes-biens-show', [
            'property' => $property->load([
                'building.address',
                'address',
                'media.tags',
                'rooms.media',
                'notes.author',
                'notes.editor',
            ]),
        ]);
    }

    /**
     * Read-only "Gestion Locative" pages for the gestionnaire role: they mirror the
     * corresponding /buildings, /tenants and /bank-accounts admin pages but are scoped
     * to the properties the connected user manages, and never expose create/edit/delete
     * actions. The routes are locked to role:gestionnaire (see routes/web.php).
     */
    public function myBuildings(): View
    {
        $user = auth()->user();

        $buildings = Building::with(['address', 'media' => fn ($query) => $query->where('kind', 'photo')->where('is_primary', true)])
            ->whereHas('properties', fn ($q) => $q->whereHas('managers', fn ($q2) => $q2->whereKey($user->id)))
            ->withCount('properties')
            ->orderBy('name')
            ->get();

        return view('menus.mes-immeubles', ['buildings' => $buildings]);
    }

    public function showBuilding(Building $building): View
    {
        $user = auth()->user();

        abort_unless($building->isManagedBy($user), 403);

        return view('menus.mes-immeubles-show', [
            'building' => $building->load(['address', 'properties', 'media.tags', 'notes.author', 'notes.editor']),
        ]);
    }

    public function myTenants(): View
    {
        $user = auth()->user();

        $tenants = Tenant::with(['media' => fn ($query) => $query->where('kind', 'photo')->where('is_primary', true)])
            ->whereHas('properties', fn ($q) => $q->whereHas('managers', fn ($q2) => $q2->whereKey($user->id)))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('menus.mes-locataires', ['tenants' => $tenants]);
    }

    public function showTenant(Tenant $tenant): View
    {
        $user = auth()->user();

        abort_unless($tenant->isManagedBy($user), 403);

        return view('menus.mes-locataires-show', [
            'tenant' => $tenant->load(['media.tags', 'notes.author', 'notes.editor', 'properties']),
        ]);
    }

    public function myBankAccounts(): View
    {
        $user = auth()->user();

        $bankAccounts = BankAccount::with('manager')
            ->whereHas('properties', fn ($q) => $q->whereHas('managers', fn ($q2) => $q2->whereKey($user->id)))
            ->orderBy('label')
            ->get();

        return view('menus.mes-comptes-bancaires', ['bankAccounts' => $bankAccounts]);
    }

    public function showBankAccount(BankAccount $bankAccount): View
    {
        $user = auth()->user();

        abort_unless($bankAccount->isManagedBy($user), 403);

        $bankAccount->load(['manager', 'reconciliations.createdBy']);

        $transactions = $bankAccount->transactions()->with('reconciliation')->orderByDesc('date')->get();

        return view('menus.mes-comptes-bancaires-show', [
            'bankAccount' => $bankAccount,
            'transactions' => $transactions,
            'openReconciliation' => $bankAccount->reconciliations()->whereNull('closed_at')->first(),
        ]);
    }
}
