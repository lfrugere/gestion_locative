<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Administration') · {{ config('app.name') }}</title>
        <style>
            :root { color: #1d2939; background: #f6f8fb; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
            * { box-sizing: border-box; }
            body { margin: 0; font-size: 15px; line-height: 1.45; }
            a { color: #176b66; text-decoration: none; }
            a:hover { text-decoration: underline; }
            .admin-shell { display: grid; min-height: 100vh; grid-template-columns: 230px 1fr; }
            .admin-nav { padding: 24px 18px; color: #dce8ee; background: #183b56; }
            .admin-brand { display: block; margin: 0 10px 32px; color: #fff; font-weight: 750; }
            .admin-nav a { display: block; margin: 4px 0; padding: 10px 12px; border-radius: 8px; color: #c9d6df; }
            .admin-nav a:hover { color: #fff; background: rgba(255, 255, 255, .1); text-decoration: none; }
            .admin-nav form { margin: 26px 0 0; }
            .admin-nav button { padding: 0 12px; border: 0; color: #9dd9d1; background: transparent; cursor: pointer; font: inherit; }
            .admin-main { max-width: 1200px; width: 100%; padding: 34px clamp(24px, 4vw, 52px); }
            .admin-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin-bottom: 24px; }
            h1 { margin: 0; color: #183b56; font-size: clamp(1.65rem, 2.5vw, 2.15rem); letter-spacing: -.035em; }
            h2 { margin-top: 0; color: #183b56; font-size: 1.2rem; }
            .muted { color: #667085; }
            .button { display: inline-block; padding: 8px 13px; border: 0; border-radius: 8px; color: #fff; background: #2a9d8f; cursor: pointer; font: inherit; font-weight: 700; }
            .button:hover { background: #23877b; text-decoration: none; }
            .button.secondary { color: #344054; background: #eaecf0; }
            .card { padding: 20px; border: 1px solid #eaecf0; border-radius: 14px; background: #fff; box-shadow: 0 8px 24px rgba(16, 24, 40, .05); }
            .flash { margin-bottom: 20px; padding: 12px 15px; border-radius: 9px; color: #067647; background: #ecfdf3; }
            .table-wrap { overflow-x: auto; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 12px 10px; border-bottom: 1px solid #eaecf0; text-align: left; vertical-align: top; }
            th { color: #667085; font-size: .78rem; letter-spacing: .06em; text-transform: uppercase; }
            .empty { padding: 32px 12px; color: #667085; text-align: center; }
            .form-grid { display: grid; gap: 14px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .form-field.full { grid-column: 1 / -1; }
            label { display: block; margin-bottom: 7px; color: #344054; font-size: .9rem; font-weight: 700; }
            input, select, textarea { width: 100%; padding: 9px 11px; border: 1px solid #d0d5dd; border-radius: 8px; color: #1d2939; background: #fff; font: inherit; }
            textarea { min-height: 100px; resize: vertical; }
            input:focus, select:focus, textarea:focus { border-color: #2a9d8f; outline: 3px solid rgba(42, 157, 143, .14); }
            .errors { margin-bottom: 20px; padding: 12px 15px; border-radius: 9px; color: #b42318; background: #fff3f2; }
            .form-actions { display: flex; gap: 10px; margin-top: 24px; }
            .actions { display: flex; flex-wrap: wrap; gap: 10px; white-space: nowrap; }
            .actions form { display: inline; }
            .link-button { padding: 0; border: 0; color: #b42318; background: transparent; cursor: pointer; font: inherit; }
            .icon-action { position: relative; display: inline-grid; width: 34px; height: 34px; place-items: center; padding: 0; border: 1px solid #d8e2ec; border-radius: 9px; color: #0f766e; background: #fff; cursor: pointer; font: inherit; font-size: 1.2rem; line-height: 1; text-decoration: none; transition: background .15s ease, border-color .15s ease, transform .15s ease; }
            .icon-action:hover, .icon-action:focus-visible { border-color: #5eead4; color: #0f766e; background: #e6fffb; outline: 0; text-decoration: none; transform: translateY(-1px); }
            .icon-action::after { position: absolute; z-index: 20; top: calc(100% + 8px); left: 50%; padding: 5px 8px; border-radius: 6px; color: #fff; background: #102a43; content: attr(data-tooltip); font-size: .73rem; font-weight: 700; line-height: 1; opacity: 0; pointer-events: none; transform: translate(-50%, -4px); transition: opacity .15s ease, transform .15s ease; }
            .icon-action:hover::after, .icon-action:focus-visible::after { opacity: 1; transform: translate(-50%, 0); }
            .danger-action { color: #b42318; }
            .danger-action:hover, .danger-action:focus-visible { border-color: #fecaca; color: #b42318; background: #fff1f0; }
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
            .detail-hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin: -34px clamp(-52px, -4vw, -24px) 30px; padding: 40px clamp(24px, 4vw, 52px) 32px; color: #f8fafc; background: radial-gradient(circle at 88% 5%, rgba(45, 212, 191, .22), transparent 30%), linear-gradient(118deg, #0f2742, #164e63); }
            .detail-hero h1 { max-width: 760px; color: #fff; font-size: clamp(2.1rem, 3.8vw, 3.2rem); line-height: 1.04; }
            .detail-hero-copy { min-width: 0; }
            .eyebrow, .panel-kicker { color: #0f766e; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
            .detail-hero .eyebrow { color: #99f6e4; margin: 14px 0 8px; }
            .back-link { color: #cbd5e1; font-size: .9rem; font-weight: 650; }
            .back-link:hover { color: #fff; }
            .detail-lead { margin: 12px 0 0; color: #d5e4ef; font-size: .98rem; }
            .detail-hero-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 10px; }
            .detail-hero-actions .secondary { color: #0f2742; background: #fff; }
            .detail-grid { display: grid; align-items: start; gap: 22px; grid-template-columns: minmax(0, 1fr) minmax(290px, 350px); }
            .detail-main { display: grid; gap: 18px; }
            .detail-aside { display: grid; gap: 18px; position: sticky; top: 24px; }
            .detail-panel, .map-card, .cover-card { border: 1px solid #e4eaf0; border-radius: 16px; background: #fff; box-shadow: 0 14px 30px rgba(15, 39, 66, .06); }
            .detail-panel { padding: 22px; }
            .panel-heading, .map-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin-bottom: 18px; }
            .panel-heading h2, .map-card-header h2 { margin: 5px 0 0; color: #102a43; font-size: 1.12rem; }
            .panel-icon, .map-pin { display: grid; width: 36px; height: 36px; place-items: center; border-radius: 11px; color: #0f766e; background: #e6fffb; font-size: 1.35rem; }
            .map-pin { color: #fff; background: #0f766e; font-size: 1.05rem; }
            .address-value { margin: 0; color: #243b53; font-size: .98rem; line-height: 1.65; }
            .notes-block { margin-top: 22px; padding-top: 18px; border-top: 1px solid #edf1f5; }
            .notes-block > span { color: #627d98; font-size: .75rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
            .notes-block p { margin: 7px 0 0; color: #486581; line-height: 1.6; white-space: pre-line; }
            .status-pill { display: inline-flex; align-items: center; justify-content: center; width: max-content; min-height: 27px; padding: 4px 10px; border-radius: 999px; font-size: .78rem; font-weight: 750; white-space: nowrap; }
            .status-active { color: #087443; background: #dcfae6; }
            .status-muted { color: #475467; background: #eef2f6; }
            .count-badge { display: grid; min-width: 32px; height: 32px; place-items: center; border-radius: 50%; color: #fff; background: #0f766e; font-size: .83rem; font-weight: 800; }
            .associated-list { display: grid; }
            .associated-row, .related-entity { display: grid; grid-template-columns: auto minmax(0, 1fr) auto auto; gap: 13px; align-items: center; padding: 14px 2px; border-top: 1px solid #edf1f5; color: #243b53; }
            .associated-row:hover, .related-entity:hover { text-decoration: none; }
            .associated-row:hover strong, .related-entity:hover strong { color: #0f766e; }
            .associated-row strong, .related-entity strong { display: block; font-size: .96rem; }
            .associated-row small, .related-entity small { display: block; margin-top: 3px; color: #627d98; font-size: .82rem; }
            .entity-mark { display: grid; width: 35px; height: 35px; place-items: center; border-radius: 10px; color: #164e63; background: #e5f4fa; font-size: .82rem; font-weight: 850; }
            .row-arrow { color: #829ab1; font-size: 1.15rem; }
            .metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border: 1px solid #edf1f5; border-radius: 12px; overflow: hidden; }
            .metric { min-height: 86px; padding: 15px; border-right: 1px solid #edf1f5; }
            .metric:last-child { border-right: 0; }
            .metric span { display: block; color: #829ab1; font-size: .76rem; font-weight: 700; }
            .metric strong { display: block; margin-top: 8px; color: #102a43; font-size: 1.02rem; }
            .related-entity { padding: 0; border-top: 0; }
            .map-card { overflow: hidden; }
            .map-card-header { margin: 0; padding: 20px 20px 16px; }
            .address-map { height: 310px; border-radius: 0; }
            .map-card-footer { display: flex; justify-content: space-between; gap: 12px; padding: 13px 16px; color: #627d98; font-size: .76rem; }
            .map-card-footer a { color: #0f766e; font-weight: 700; }
            .map-empty { display: grid; min-height: 190px; place-items: center; padding: 25px; color: #627d98; background: linear-gradient(135deg, #f7fafc, #edf8f7); text-align: center; }
            .map-empty span { color: #0f766e; font-size: 2rem; }
            .map-empty p { margin: 0; max-width: 220px; }
            .cover-card { overflow: hidden; }
            .cover-card img { display: block; width: 100%; aspect-ratio: 16 / 10; object-fit: cover; }
            .cover-card div { padding: 14px 16px; }
            .cover-card span, .cover-card strong { display: block; }
            .cover-card span { color: #829ab1; font-size: .74rem; font-weight: 750; text-transform: uppercase; }
            .cover-card strong { margin-top: 5px; color: #243b53; font-size: .9rem; }
            .danger-zone { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 20px 22px; border: 1px solid #f7d7d3; border-radius: 14px; background: #fff8f7; }
            .danger-zone strong { color: #9b1c1c; }
            .danger-zone p { margin: 5px 0 0; color: #9b5b52; font-size: .86rem; }
            .dashboard-header { align-items: flex-end; }
            .dashboard-lead { margin: 8px 0 0; color: #627d98; }
            .dashboard-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
            .dashboard-metrics { display: grid; gap: 14px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 22px; }
            .dashboard-metric { min-height: 132px; padding: 18px; border: 1px solid #e4eaf0; border-radius: 14px; color: #243b53; background: #fff; box-shadow: 0 8px 20px rgba(15, 39, 66, .04); transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
            .dashboard-metric:hover { border-color: #99f6e4; box-shadow: 0 12px 24px rgba(15, 118, 110, .1); text-decoration: none; transform: translateY(-2px); }
            .dashboard-metric span, .dashboard-metric strong, .dashboard-metric small { display: block; }
            .dashboard-metric span { color: #627d98; font-size: .8rem; font-weight: 700; }
            .dashboard-metric strong { margin: 8px 0 3px; color: #102a43; font-size: 1.8rem; line-height: 1; }
            .dashboard-metric small { color: #0f766e; font-size: .78rem; font-weight: 700; }
            .dashboard-grid { display: grid; gap: 22px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .dashboard-lists { margin-top: 22px; }
            .dashboard-panel { min-width: 0; }
            .panel-link { color: #0f766e; font-size: .78rem; font-weight: 700; white-space: nowrap; }
            .attention-list, .quick-links { display: grid; border-top: 1px solid #edf1f5; }
            .attention-list > a, .quick-links > a, .dashboard-row { display: grid; align-items: center; gap: 12px; padding: 13px 0; border-bottom: 1px solid #edf1f5; color: #243b53; }
            .attention-list > a { grid-template-columns: auto minmax(0, 1fr) auto; }
            .quick-links > a { grid-template-columns: auto minmax(0, 1fr) auto; }
            .dashboard-row { grid-template-columns: auto minmax(0, 1fr) auto; }
            .dashboard-row:has(.status-pill) { grid-template-columns: auto minmax(0, 1fr) auto auto; }
            .attention-list > a:last-child, .quick-links > a:last-child, .dashboard-row:last-child { border-bottom: 0; }
            .attention-list > a:hover, .quick-links > a:hover, .dashboard-row:hover { color: #0f766e; text-decoration: none; }
            .attention-list strong, .quick-links strong, .dashboard-row strong { display: block; font-size: .9rem; }
            .attention-list small, .quick-links small, .dashboard-row small { display: block; margin-top: 3px; color: #627d98; font-size: .78rem; }
            .attention-count { display: grid; width: 31px; height: 31px; place-items: center; border-radius: 10px; color: #0f766e; background: #e6fffb; font-size: .82rem; font-weight: 800; }
            .media-card { padding: 22px; border-radius: 16px; box-shadow: 0 14px 30px rgba(15, 39, 66, .06); }
            .media-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
            .media-card h2 { margin: 5px 0 0; }
            .media-card-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
            .media-section-title { margin: 22px 0 12px; color: #344054; font-size: .9rem; }
            .photo-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .photo-item { overflow: hidden; padding: 0; border: 1px solid #e4eaf0; border-radius: 12px; background: #fff; }
            .photo-item.primary { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20, 184, 166, .12); }
            .photo-preview { display: block; width: 100%; padding: 0; border: 0; color: inherit; background: transparent; cursor: zoom-in; text-align: left; }
            .photo-preview:focus-visible { outline: 3px solid rgba(42, 157, 143, .35); outline-offset: -3px; }
            .photo-preview img { display: block; width: 100%; height: 118px; border-radius: 0; object-fit: cover; transition: transform .2s ease, filter .2s ease; }
            .photo-preview:hover img { filter: brightness(.94); transform: scale(1.025); }
            .photo-name { display: block; overflow: hidden; padding: 9px 10px 0; color: #344054; font-size: .8rem; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
            .photo-item-content { min-height: 18px; padding: 8px 10px 10px; }
            .photo-item-content:empty { display: none; }
            .photo-badge { display: inline-flex; padding: 3px 7px; border-radius: 999px; color: #087443; background: #dcfae6; font-size: .7rem; font-weight: 750; }
            .photo-actions { margin-top: 8px; gap: 8px; }
            .text-action { padding: 0; border: 0; color: #0f766e; background: transparent; cursor: pointer; font: inherit; font-size: .82rem; font-weight: 700; }
            .text-action:hover { text-decoration: underline; }
            .danger-text-action { color: #b42318; }
            .attachment-list { border-top: 1px solid #edf1f5; }
            .attachment-row { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px 16px; padding: 13px 0; }
            .attachment-info { display: grid; gap: 3px; }
            .attachment-info > span { color: #667085; font-size: .8rem; }
            .attachment-actions { display: flex; gap: 12px; align-items: center; }
            .field-optional { color: #829ab1; font-size: .8rem; font-weight: 500; }
            .modal-dialog { width: min(100% - 32px, 520px); padding: 0; border: 0; border-radius: 14px; color: #1d2939; box-shadow: 0 24px 60px rgba(15, 39, 66, .24); }
            .modal-dialog::backdrop { background: rgba(15, 39, 66, .48); backdrop-filter: blur(2px); }
            .modal-content { padding: 26px; }
            .modal-content h2 { margin: 5px 0 20px; }
            .modal-form { display: grid; gap: 15px; }
            .modal-form .form-actions { margin-top: 2px; }
            .modal-close-form { position: absolute; top: 10px; right: 10px; }
            .modal-close-form button { display: grid; width: 30px; height: 30px; place-items: center; padding: 0; border: 0; border-radius: 8px; color: #667085; background: #f2f4f7; cursor: pointer; font-size: 1.2rem; line-height: 1; }
            .photo-viewer-dialog { width: min(100% - 32px, 1040px); padding: 0; border: 0; background: transparent; }
            .photo-viewer-dialog::backdrop { background: rgba(9, 23, 39, .78); backdrop-filter: blur(3px); }
            .photo-viewer-dialog .modal-close-form { z-index: 1; }
            .photo-viewer-dialog .modal-close-form button { color: #fff; background: rgba(15, 39, 66, .8); }
            .photo-viewer-content { display: grid; grid-template-columns: 44px minmax(0, 1fr) 44px; gap: 14px; align-items: center; }
            .photo-viewer-content figure { display: grid; gap: 10px; margin: 0; text-align: center; }
            .photo-viewer-content img { display: block; max-width: 100%; max-height: min(75vh, 760px); margin: auto; border-radius: 10px; background: #102a43; object-fit: contain; }
            .photo-viewer-content figcaption { color: #fff; font-size: .9rem; font-weight: 650; }
            .photo-viewer-navigation { display: grid; width: 44px; height: 44px; place-items: center; padding: 0; border: 0; border-radius: 50%; color: #fff; background: rgba(255, 255, 255, .16); cursor: pointer; font-size: 2rem; line-height: 1; }
            .photo-viewer-navigation:hover:not(:disabled), .photo-viewer-navigation:focus-visible:not(:disabled) { background: rgba(255, 255, 255, .3); outline: 0; }
            .photo-viewer-navigation:disabled { cursor: default; opacity: .28; }
            .compact { padding: 12px 0 2px; }
            @media (max-width: 980px) { .detail-grid { grid-template-columns: 1fr; } .detail-aside { position: static; grid-template-columns: minmax(0, 1fr) minmax(250px, 330px); } .dashboard-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (max-width: 720px) { .detail-hero { margin: -28px -18px 28px; padding: 34px 18px 30px; } .detail-hero-actions { justify-content: flex-start; } .detail-grid { gap: 18px; } .detail-panel, .media-card { padding: 20px; } .detail-aside { grid-template-columns: 1fr; } .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .metric:nth-child(2) { border-right: 0; } .metric:nth-child(-n+2) { border-bottom: 1px solid #edf1f5; } .associated-row { grid-template-columns: auto minmax(0, 1fr) auto; } .associated-row .status-pill { display: none; } .danger-zone { align-items: flex-start; flex-direction: column; } .photo-viewer-content { grid-template-columns: 36px minmax(0, 1fr) 36px; gap: 6px; } .photo-viewer-navigation { width: 36px; height: 36px; } .dashboard-header { align-items: stretch; } .dashboard-actions { justify-content: flex-start; } .dashboard-metrics, .dashboard-grid { grid-template-columns: 1fr; } .dashboard-metric { min-height: 104px; } }
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
                @if ($errors->any())
                    <div class="errors" role="alert">
                        <strong>Le formulaire contient des erreurs :</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </body>
</html>
