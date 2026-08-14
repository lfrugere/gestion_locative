<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Administration') · {{ config('app.name') }}</title>
        <style>
            :root { color: #1d2939; background: #f6f8fb; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
            * { box-sizing: border-box; }
            body { margin: 0; }
            a { color: #176b66; text-decoration: none; }
            a:hover { text-decoration: underline; }
            .admin-shell { display: grid; min-height: 100vh; grid-template-columns: 230px 1fr; }
            .admin-nav { padding: 24px 18px; color: #dce8ee; background: #183b56; }
            .admin-brand { display: block; margin: 0 10px 32px; color: #fff; font-weight: 750; }
            .admin-nav a { display: block; margin: 4px 0; padding: 10px 12px; border-radius: 8px; color: #c9d6df; }
            .admin-nav a:hover { color: #fff; background: rgba(255, 255, 255, .1); text-decoration: none; }
            .admin-nav form { margin: 26px 0 0; }
            .admin-nav button { padding: 0 12px; border: 0; color: #9dd9d1; background: transparent; cursor: pointer; font: inherit; }
            .admin-main { max-width: 1200px; width: 100%; padding: 42px clamp(24px, 5vw, 64px); }
            .admin-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; margin-bottom: 30px; }
            h1 { margin: 0; color: #183b56; font-size: clamp(1.8rem, 3vw, 2.5rem); letter-spacing: -.04em; }
            h2 { margin-top: 0; color: #183b56; }
            .muted { color: #667085; }
            .button { display: inline-block; padding: 10px 15px; border: 0; border-radius: 8px; color: #fff; background: #2a9d8f; cursor: pointer; font: inherit; font-weight: 700; }
            .button:hover { background: #23877b; text-decoration: none; }
            .button.secondary { color: #344054; background: #eaecf0; }
            .card { padding: 24px; border: 1px solid #eaecf0; border-radius: 14px; background: #fff; box-shadow: 0 8px 24px rgba(16, 24, 40, .05); }
            .flash { margin-bottom: 20px; padding: 12px 15px; border-radius: 9px; color: #067647; background: #ecfdf3; }
            .table-wrap { overflow-x: auto; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 14px 12px; border-bottom: 1px solid #eaecf0; text-align: left; vertical-align: top; }
            th { color: #667085; font-size: .78rem; letter-spacing: .06em; text-transform: uppercase; }
            .empty { padding: 32px 12px; color: #667085; text-align: center; }
            .form-grid { display: grid; gap: 18px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .form-field.full { grid-column: 1 / -1; }
            label { display: block; margin-bottom: 7px; color: #344054; font-size: .9rem; font-weight: 700; }
            input, select, textarea { width: 100%; padding: 11px 12px; border: 1px solid #d0d5dd; border-radius: 8px; color: #1d2939; background: #fff; font: inherit; }
            textarea { min-height: 100px; resize: vertical; }
            input:focus, select:focus, textarea:focus { border-color: #2a9d8f; outline: 3px solid rgba(42, 157, 143, .14); }
            .errors { margin-bottom: 20px; padding: 12px 15px; border-radius: 9px; color: #b42318; background: #fff3f2; }
            .form-actions { display: flex; gap: 10px; margin-top: 24px; }
            .actions { display: flex; flex-wrap: wrap; gap: 10px; white-space: nowrap; }
            .actions form { display: inline; }
            .link-button { padding: 0; border: 0; color: #b42318; background: transparent; cursor: pointer; font: inherit; }
            .button.danger { background: #b42318; }
            .button.danger:hover { background: #912018; }
            .list-thumb { width: 56px; height: 56px; border-radius: 6px; object-fit: cover; }
            .media-card { margin-top: 24px; }
            .photo-grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .photo-item { padding: 10px; border: 1px solid #eaecf0; border-radius: 10px; }
            .photo-item.primary { border-color: #2a9d8f; }
            .photo-item img { width: 100%; height: 120px; border-radius: 6px; object-fit: cover; }
            .photo-item span { display: block; margin: 8px 0; font-size: .85rem; }
            .attachment-row { display: grid; gap: 10px; align-items: center; grid-template-columns: minmax(160px, 1fr) auto auto; padding: 12px 0; border-bottom: 1px solid #eaecf0; }
            .attachment-edit { display: flex; flex-wrap: wrap; gap: 8px; grid-column: 1 / -1; }
            .attachment-edit input { flex: 1 1 180px; }
            .tags { padding: 3px 8px; border-radius: 10px; color: #475467; background: #f2f4f7; font-size: .8rem; }
            .upload-form { margin-top: 24px; padding-top: 20px; border-top: 1px solid #eaecf0; }
            .address-map { height: 280px; border-radius: 10px; }
            .hint { margin: 6px 0 0; color: #667085; font-size: .82rem; }
            @media (max-width: 720px) { .admin-shell { display: block; } .admin-nav { padding: 16px; } .admin-brand { margin-bottom: 12px; } .admin-nav a { display: inline-block; } .admin-nav form { display: inline-block; margin: 0; } .admin-main { padding: 28px 18px; } .form-grid { grid-template-columns: 1fr; } .form-field.full { grid-column: auto; } .admin-header { align-items: stretch; flex-direction: column; } }
        </style>
    </head>
    <body>
        <div class="admin-shell">
            <aside class="admin-nav">
                <a class="admin-brand" href="{{ route('admin.dashboard') }}">Gestion locative</a>
                <nav aria-label="Administration">
                    <a href="{{ route('admin.dashboard') }}">Vue d’ensemble</a>
                    @can('view buildings')
                        <a href="{{ route('admin.buildings.index') }}">Immeubles</a>
                    @endcan
                    @can('view properties')
                        <a href="{{ route('admin.properties.index') }}">Biens</a>
                    @endcan
                </nav>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Se déconnecter</button>
                </form>
            </aside>

            <main class="admin-main">
                @if (session('success'))
                    <div class="flash" role="status">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="errors" role="alert">{{ session('error') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </body>
</html>
