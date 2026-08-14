<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <title>Connexion · {{ config('app.name') }}</title>
        <style>
            :root {
                --ink: #1d2939;
                --muted: #667085;
                --line: #d0d5dd;
                --paper: #ffffff;
                --wash: #f6f8fb;
                --navy: #183b56;
                --teal: #2a9d8f;
                --danger: #b42318;
                --danger-wash: #fff3f2;
            }

            * { box-sizing: border-box; }

            body {
                min-height: 100vh;
                margin: 0;
                color: var(--ink);
                background: var(--wash);
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            .login-shell {
                display: grid;
                min-height: 100vh;
                grid-template-columns: minmax(0, 1.05fr) minmax(420px, 0.95fr);
            }

            .login-intro {
                display: flex;
                position: relative;
                flex-direction: column;
                justify-content: space-between;
                overflow: hidden;
                padding: 48px clamp(32px, 7vw, 112px);
                color: #fff;
                background: var(--navy);
            }

            .login-intro::before,
            .login-intro::after {
                position: absolute;
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 999px;
                content: "";
            }

            .login-intro::before {
                width: 520px;
                height: 520px;
                right: -220px;
                top: -180px;
            }

            .login-intro::after {
                width: 360px;
                height: 360px;
                bottom: -180px;
                left: -160px;
            }

            .brand,
            .intro-copy,
            .intro-footer { position: relative; z-index: 1; }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                width: fit-content;
                font-size: 1rem;
                font-weight: 700;
                letter-spacing: 0.02em;
            }

            .brand-mark {
                display: grid;
                width: 42px;
                height: 42px;
                place-items: center;
                border-radius: 13px;
                color: var(--navy);
                background: #f4c95d;
                box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
            }

            .brand-mark svg { width: 23px; height: 23px; }

            .intro-copy { max-width: 540px; margin: auto 0; }

            .eyebrow {
                margin: 0 0 18px;
                color: #9dd9d1;
                font-size: 0.76rem;
                font-weight: 800;
                letter-spacing: 0.16em;
                text-transform: uppercase;
            }

            .intro-copy h1 {
                max-width: 500px;
                margin: 0;
                font-family: Georgia, "Times New Roman", serif;
                font-size: clamp(2.8rem, 5vw, 5.4rem);
                font-weight: 400;
                letter-spacing: -0.055em;
                line-height: 0.98;
            }

            .intro-copy p {
                max-width: 430px;
                margin: 26px 0 0;
                color: #c9d6df;
                font-size: 1.05rem;
                line-height: 1.7;
            }

            .intro-footer {
                color: #9fb2c0;
                font-size: 0.82rem;
            }

            .login-panel {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 32px;
                background: var(--paper);
            }

            .login-card { width: min(100%, 410px); }

            .login-card h2 {
                margin: 0;
                color: var(--ink);
                font-size: 1.85rem;
                letter-spacing: -0.035em;
            }

            .login-card .subtitle {
                margin: 10px 0 30px;
                color: var(--muted);
                line-height: 1.55;
            }

            .alert {
                margin-bottom: 22px;
                padding: 13px 15px;
                border: 1px solid #fecdca;
                border-radius: 10px;
                color: var(--danger);
                background: var(--danger-wash);
                font-size: 0.9rem;
                line-height: 1.45;
            }

            .field { margin-bottom: 20px; }

            .field label {
                display: block;
                margin-bottom: 8px;
                color: var(--ink);
                font-size: 0.88rem;
                font-weight: 700;
            }

            .field input {
                width: 100%;
                padding: 13px 14px;
                border: 1px solid var(--line);
                border-radius: 9px;
                color: var(--ink);
                background: #fff;
                font: inherit;
                outline: none;
                transition: border-color 150ms ease, box-shadow 150ms ease;
            }

            .field input::placeholder { color: #98a2b3; }

            .field input:focus {
                border-color: var(--teal);
                box-shadow: 0 0 0 4px rgba(42, 157, 143, 0.14);
            }

            .form-options {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin: 2px 0 25px;
            }

            .remember {
                display: inline-flex;
                align-items: center;
                gap: 9px;
                color: var(--muted);
                font-size: 0.88rem;
                cursor: pointer;
            }

            .remember input { width: 16px; height: 16px; accent-color: var(--teal); }

            .submit-button {
                width: 100%;
                padding: 13px 18px;
                border: 0;
                border-radius: 9px;
                color: #fff;
                background: var(--teal);
                box-shadow: 0 8px 18px rgba(42, 157, 143, 0.22);
                font: inherit;
                font-weight: 750;
                cursor: pointer;
                transition: background 150ms ease, transform 150ms ease, box-shadow 150ms ease;
            }

            .submit-button:hover {
                background: #23877b;
                box-shadow: 0 10px 22px rgba(42, 157, 143, 0.3);
                transform: translateY(-1px);
            }

            .submit-button:focus-visible {
                outline: 3px solid rgba(42, 157, 143, 0.32);
                outline-offset: 3px;
            }

            .security-note {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                margin: 23px 0 0;
                color: #98a2b3;
                font-size: 0.78rem;
            }

            .security-note svg { width: 14px; height: 14px; }

            @media (max-width: 800px) {
                .login-shell { display: block; }
                .login-intro { min-height: 310px; padding: 28px 24px; }
                .intro-copy { margin-top: 48px; }
                .intro-copy h1 { font-size: clamp(2.7rem, 12vw, 4.4rem); }
                .intro-copy p, .intro-footer { display: none; }
                .login-panel { min-height: calc(100vh - 310px); padding: 42px 24px; }
            }

            @media (max-width: 420px) {
                .login-intro { min-height: 270px; }
                .login-panel { min-height: calc(100vh - 270px); }
                .form-options { align-items: flex-start; flex-direction: column; }
            }
        </style>
    </head>
    <body>
        <div class="login-shell">
            <section class="login-intro" aria-label="Présentation">
                <div class="brand">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 20V8l8-4 8 4v12" />
                            <path d="M8 20v-5h8v5M8 10h.01M12 10h.01M16 10h.01" />
                        </svg>
                    </span>
                    <span>{{ config('app.name', 'Gestion locative') }}</span>
                </div>

                <div class="intro-copy">
                    <p class="eyebrow">Votre espace de gestion</p>
                    <h1>Un logement bien géré, simplement.</h1>
                    <p>Retrouvez vos appartements, vos colocations et le parcours de chaque locataire au même endroit.</p>
                </div>

                <p class="intro-footer">Un espace privé réservé à votre équipe.</p>
            </section>

            <section class="login-panel">
                <div class="login-card">
                    <h2>Content de vous revoir</h2>
                    <p class="subtitle">Connectez-vous pour accéder à votre espace de gestion.</p>

                    @if ($errors->any())
                        <div class="alert" role="alert">
                            Les informations de connexion sont incorrectes. Vérifiez votre e-mail et votre mot de passe.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="field">
                            <label for="email">Adresse e-mail</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="vous@exemple.fr" required autofocus autocomplete="username">
                        </div>

                        <div class="field">
                            <label for="password">Mot de passe</label>
                            <input id="password" name="password" type="password" placeholder="Votre mot de passe" required autocomplete="current-password">
                        </div>

                        <div class="form-options">
                            <label class="remember">
                                <input name="remember" type="checkbox" value="1">
                                <span>Se souvenir de moi</span>
                            </label>
                        </div>

                        <button class="submit-button" type="submit">Se connecter</button>
                    </form>

                    <p class="security-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="16" height="11" x="4" y="10" rx="2" />
                            <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" />
                        </svg>
                        Accès sécurisé à vos données
                    </p>
                </div>
            </section>
        </div>
    </body>
</html>
