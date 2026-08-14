# Gestion locative

Application Laravel de gestion d'appartements, de chambres en colocation et du parcours des locataires.

## Prérequis locaux

- PHP 8.3 ou supérieur avec l'extension SQLite activée
- Composer
- Docker et Docker Compose pour l'exécution conteneurisée

## Installation locale

Depuis la racine du projet :

```powershell
php composer.phar install --no-scripts
php artisan key:generate
php artisan migrate
php artisan serve
```

L'application est alors disponible sur http://localhost:8000.

## Exécution avec Docker Compose

Copier `.env.example` vers `.env`, définir `APP_KEY` avec `php artisan key:generate`, puis lancer :

```powershell
docker compose up --build -d
```

Le fichier SQLite local `database/database.sqlite` est utilisé par l'application, y compris dans Docker. Les fichiers générés sont conservés dans le volume `storage_data`.

Pour arrêter les conteneurs sans supprimer les données :

```powershell
docker compose down
```

## Tests

```powershell
php artisan test
```
