@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')
    <div class="admin-header">
        <div><p class="muted">Admin Général</p><h1>Utilisateurs</h1></div>
        <a class="button" href="{{ route('users.create') }}">Ajouter un utilisateur</a>
    </div>

    <div class="card table-wrap">
        @if ($users->isEmpty())
            <p class="empty">Aucun utilisateur n’a été trouvé.</p>
        @else
            <table>
                <thead><tr><th>Nom</th><th>Email</th><th>Rôles</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="status-pill">{{ $role->name }}</span>
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td class="actions">
                                <a class="icon-action" href="{{ route('users.edit', $user) }}" aria-label="Modifier {{ $user->name }}" data-tooltip="Modifier" title="Modifier"><span aria-hidden="true">✎</span></a>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?')">@csrf @method('DELETE')<button class="icon-action danger-action" type="submit" aria-label="Supprimer {{ $user->name }}" data-tooltip="Supprimer" title="Supprimer"><span aria-hidden="true">×</span></button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
