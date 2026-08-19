<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
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
        return view('admin.tenants.create');
    }

    public function show(Tenant $tenant): View
    {
        return view('admin.tenants.show', [
            'tenant' => $tenant->load(['media.tags', 'notes.author', 'notes.editor']),
        ]);
    }

    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        return $this->save($request, $tenant);
    }

    public function destroy(Tenant $tenant, MediaManager $manager): RedirectResponse
    {
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

        $validated = $request->validate([
            'civility' => ['required', Rule::in(array_keys(Tenant::CIVILITY_LABELS))],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::in(array_keys(Tenant::STATUS_LABELS))],
        ]);

        if ($isNew || $tenant->status !== $validated['status']) {
            $validated['status_changed_at'] = now();
        }

        if (! $isNew) {
            $tenant->update($validated);
        } else {
            $tenant = Tenant::create($validated);
        }

        return to_route('tenants.index', ['status' => $tenant->status])->with(
            'success',
            $isNew ? 'Le locataire a été créé.' : 'Le locataire a été modifié.',
        );
    }
}
