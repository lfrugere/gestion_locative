<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Tableau de bord - {{ config('app.name') }}</title>
    </head>
    <body>
        <main>
            <h1>Tableau de bord</h1>
            <p>Bienvenue, {{ auth()->user()->name }}.</p>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Se déconnecter</button>
            </form>
        </main>
    </body>
</html>
