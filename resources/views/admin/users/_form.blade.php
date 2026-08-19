@if ($errors->any())
    <div class="errors" role="alert"><strong>Veuillez corriger les erreurs suivantes :</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form class="card" method="POST" action="{{ $action }}">
    @csrf
    @if ($user) @method('PUT') @endif
    <div class="form-grid">
        <div class="form-field"><label for="name">Nom</label><input id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required autofocus></div>
        <div class="form-field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required></div>
        <div class="form-field">
            <label for="password">Mot de passe @if ($user) <span class="field-optional">(laisser vide pour ne pas changer)</span> @endif</label>
            <input id="password" type="password" name="password" autocomplete="new-password" @unless ($user) required @endunless>
        </div>
        <div class="form-field">
            <label>Rôles</label>
            @foreach ($roles as $role)
                @php($selectedRoles = old('roles', $user?->roles->pluck('name')->all() ?? []))
                <label style="display:block;font-weight:400;">
                    <input type="checkbox" name="roles[]" value="{{ $role }}" @checked(in_array($role, $selectedRoles, true))>
                    {{ $role }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="form-actions"><a class="button secondary" href="{{ route('users.index') }}">Annuler</a><button class="button" type="submit">{{ $submitLabel }}</button></div>
</form>
