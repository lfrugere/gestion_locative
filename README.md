# Gestion locative

Application Laravel de gestion d'appartements, de chambres en colocation et du parcours des locataires.

Les choix d’architecture, de métier et de design destinés aux développeurs et agents IA sont décrits dans [docs/architecture.md](docs/architecture.md).

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

### Téléversement de fichiers

Les photos et pièces jointes peuvent peser jusqu’à 20 Mo. Pour que PHP local accepte cette taille, modifier le fichier indiqué par `php --ini` (sur ce poste : `C:\Tools\DEV\dev\php\php-8.3.3-nts-Win32-vs16-x64\php.ini`) :

```ini
upload_max_filesize = 20M
post_max_size = 24M
```

Fermer puis rouvrir le terminal avant de relancer `php artisan serve`.

## Authentification locale

Renseigner les variables `ADMIN_NAME`, `ADMIN_EMAIL` et `ADMIN_PASSWORD` dans `.env` pour créer un compte administrateur, et/ou `MANAGER_NAME`, `MANAGER_EMAIL` et `MANAGER_PASSWORD` pour créer un compte gestionnaire, puis lancer :

```cmd
php artisan db:seed
```

Chaque jeu de variables est optionnel et indépendant : seul un compte dont les trois variables correspondantes sont renseignées est créé, avec uniquement le rôle correspondant.

La page de connexion est disponible sur http://localhost:8000/login. L'inscription publique est désactivée ; les comptes locataires seront ajoutés ultérieurement par un processus dédié.

## Back-office d'administration

Le compte ayant le rôle `admin` peut accéder à http://localhost:8000/admin pour créer les immeubles et les logements. Le rôle `gestionnaire` consulte l'ensemble du patrimoine et gère (factures, photos, pièces jointes, notes) uniquement les biens qui lui ont été explicitement attribués. Le détail des droits par rôle et par entité est décrit dans [docs/roles-permissions.md](docs/roles-permissions.md).

Les appartements et parkings doivent être rattachés à un immeuble. Une maison possède sa propre adresse.

Les fiches immeubles et biens acceptent plusieurs photos et pièces jointes. Une photo principale est utilisée dans les listes ; les pièces jointes peuvent être renommées et taguées. Les fichiers sont stockés sur le disque privé et servis uniquement après authentification.

Les adresses sont géocodées automatiquement lors de leur création ou modification. Pour géocoder les adresses existantes :

```cmd
php artisan addresses:geocode
```

Pour vérifier les coordonnées enregistrées :

```cmd
php artisan tinker --execute="dump(App\Models\Address::first()->latitude, App\Models\Address::first()->longitude, App\Models\Address::first()->geocoded_at);"
```

Si PHP affiche `cURL error 60`, télécharger le certificat CA depuis `https://curl.se/ca/cacert.pem`, puis renseigner ces deux lignes dans le `php.ini` utilisé par `php --ini` :

```ini
curl.cainfo="C:\Tools\DEV\dev\php\cacert.pem"
openssl.cafile="C:\Tools\DEV\dev\php\cacert.pem"
```

Fermer puis rouvrir le terminal, et relancer `php artisan addresses:geocode --force`.

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
