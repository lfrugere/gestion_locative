<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::with('roles')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => Role::pluck('name'),
        ]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        return $this->save($request);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        return $this->save($request, $user);
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        abort_if($user->id === auth()->id(), 403, 'Vous ne pouvez pas supprimer votre propre compte.');

        $user->delete();

        return to_route('users.index')->with('success', 'L’utilisateur a été supprimé.');
    }

    private function save(Request $request, ?User $user = null): RedirectResponse
    {
        $isNew = $user === null;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$isNew ? 'required' : 'nullable', Password::default()],
            'roles' => ['array'],
            'roles.*' => [Rule::exists('roles', 'name')],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        if ($isNew) {
            $user = User::create($attributes);
        } else {
            $user->update($attributes);
        }

        $user->syncRoles($validated['roles'] ?? []);

        return to_route('users.index')->with(
            'success',
            $isNew ? 'L’utilisateur a été créé.' : 'L’utilisateur a été modifié.',
        );
    }
}
