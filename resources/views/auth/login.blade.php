<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Connexion - {{ config('app.name') }}</title>
    </head>
    <body>
        <main>
            <h1>Connexion</h1>

            @if ($errors->any())
                <div role="alert">
                    <p>Les informations de connexion sont incorrectes.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <label for="email">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>

                <div>
                    <label for="password">Mot de passe</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password">
                </div>

                <label>
                    <input name="remember" type="checkbox" value="1">
                    Se souvenir de moi
                </label>

                <button type="submit">Se connecter</button>
            </form>
        </main>
    </body>
</html>
