<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MediaManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status', Tenant::STATUS_ACTIVE)->toString();

        abort_unless(array_key_exists($status, Tenant::STATUS_LABELS), 404);

        return view('admin.tenants.index', [
            'status' => $status,
            'tenants' => Tenant::with([
                'media' => fn ($query) => $query->where('kind', 'photo')->where('is_primary', true),
            ])->where('status', $status)->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.tenants.create', [
            'availableProperties' => $this->availableProperties(auth()->user()),
        ]);
    }

    public function show(Tenant $tenant): View
    {
        $user = auth()->user();

        return view('admin.tenants.show', [
            'tenant' => $tenant->load(['media.tags', 'notes.author', 'notes.editor', 'properties']),
            'isTenantManager' => $user->hasRole('admin') || $tenant->isManagedBy($user),
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        $user = auth()->user();

        abort_unless($user->hasRole('admin') || $tenant->isManagedBy($user), 403);

        return view('admin.tenants.edit', [
            'tenant' => $tenant->load('properties'),
            'availableProperties' => $this->availableProperties($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($user->hasRole('admin') || $tenant->isManagedBy($user), 403);

        return $this->save($request, $tenant);
    }

    public function destroy(Tenant $tenant, MediaManager $manager): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($user->hasRole('admin') || $tenant->isManagedBy($user), 403);

        $tenant->load('media');

        foreach ($tenant->media as $media) {
            $manager->delete($media);
        }

        $tenant->delete();

        return to_route('tenants.index')->with('success', 'Le locataire a été supprimé.');
    }

    private function save(Request $request, ?Tenant $tenant = null): RedirectResponse
    {
        $isNew = $tenant === null;
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');
        $allowedPropertyIds = $this->availableProperties($user)->pluck('id')->all();

        $validated = $request->validate([
            'civility' => ['required', Rule::in(array_keys(Tenant::CIVILITY_LABELS))],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::in(array_keys(Tenant::STATUS_LABELS))],
            'properties' => [$isNew && ! $isAdmin ? 'required' : 'sometimes', 'array'],
            'properties.*' => ['integer', Rule::in($allowedPropertyIds)],
        ]);

        if ($isNew || $tenant->status !== $validated['status']) {
            $validated['status_changed_at'] = now();
        }

        $propertyIds = $validated['properties'] ?? [];
        unset($validated['properties']);

        if (! $isNew) {
            $tenant->update($validated);
        } else {
            $tenant = Tenant::create($validated);
        }

        $this->syncProperties($tenant, $propertyIds, $user, $isNew);

        return to_route('tenants.index', ['status' => $tenant->status])->with(
            'success',
            $isNew ? 'Le locataire a été créé.' : 'Le locataire a été modifié.',
        );
    }

    /**
     * Attach the tenant to the selected properties. A manager may only affect the
     * properties they manage; an admin can freely sync the whole set.
     */
    private function syncProperties(Tenant $tenant, array $propertyIds, User $user, bool $isNew): void
    {
        if ($user->hasRole('admin')) {
            $tenant->properties()->sync($propertyIds);

            return;
        }

        $managedIds = $user->managedProperties()->pluck('properties.id')->all();

        $tenant->properties()->syncWithoutDetaching($propertyIds);

        $toDetach = array_diff($managedIds, $propertyIds);

        if ($toDetach) {
            $tenant->properties()->detach($toDetach);
        }
    }

    private function availableProperties(User $user)
    {
        return $user->hasRole('admin')
            ? Property::orderBy('reference')->get()
            : $user->managedProperties()->orderBy('reference')->get();
    }
}
