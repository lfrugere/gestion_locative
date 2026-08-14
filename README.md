# Gestion locative

Application Laravel de gestion d'appartements, de chambres en colocation et du parcours des locataires.

## Prérequis locaux

- PHP 8.3 ou supérieur avec l'extension SQLite activée
- Composer
- Docker et Docker Compose pour l'exécution conteneurisée

## Installation locale

Depuis la racine du projet :

```cmd
php composer.phar install --no-scripts
php artisan key:generate
php artisan migrate
php artisan serve
```

L'application est alors disponible sur http://localhost:8000.

## Authentification locale

Renseigner les variables `ADMIN_NAME`, `ADMIN_EMAIL` et `ADMIN_PASSWORD` dans `.env`, puis créer le compte administrateur :

```cmd
php artisan db:seed
```

La page de connexion est disponible sur http://localhost:8000/login. L'inscription publique est désactivée ; les comptes locataires seront ajoutés ultérieurement par un processus dédié.

## Back-office d'administration

Le compte ayant le rôle `admin` peut accéder à http://localhost:8000/admin pour créer les immeubles et les logements. Les permissions sont préparées pour les rôles `admin`, `gestionnaire` et `locataire` ; seul le rôle `admin` peut créer les éléments dans cette première version.

Les appartements et parkings doivent être rattachés à un immeuble. Une maison possède sa propre adresse.

Les fiches immeubles et biens acceptent plusieurs photos et pièces jointes. Une photo principale est utilisée dans les listes ; les pièces jointes peuvent être renommées et taguées. Les fichiers sont stockés sur le disque privé et servis uniquement après authentification.

Les adresses sont géocodées automatiquement lors de leur création ou modification. Pour géocoder les adresses existantes :

```cmd
php artisan addresses:geocode
```

## Exécution avec Docker Compose

Copier `.env.example` vers `.env`, définir `APP_KEY` avec `php artisan key:generate`, puis lancer :

```cmd
docker compose up --build -d
```

Le fichier SQLite est situé sur le host, à l'emplacement défini par `SQLITE_DATABASE_PATH` dans `.env` (par défaut `./database/database.sqlite`). Compose le monte directement dans le conteneur ; aucun volume Docker n'est utilisé pour la base. Les fichiers générés sont conservés dans le volume `storage_data`.

Pour arrêter les conteneurs sans supprimer les données :

```cmd
docker compose down
```

## Tests

```powershell
php artisan test
```
