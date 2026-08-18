# Architecture et cohérence produit

Ce référentiel décrit les décisions à conserver lors des évolutions de l’application. Il s’adresse aux développeurs et aux agents IA qui interviennent sur le dépôt.

## 1. Objectif et périmètre

L’application permet au propriétaire ou gestionnaire de suivre son patrimoine locatif : immeubles, biens, adresses et documents associés. La volumétrie est volontairement faible ; la simplicité d’exploitation prime sur l’optimisation prématurée.

Le périmètre actuel couvre :

- les immeubles ;
- les appartements, maisons et parkings ;
- le marquage d’un appartement ou d’une maison comme colocation ;
- les pièces des biens en colocation ;
- les adresses et leur géocodage ;
- les photos, pièces jointes et tags ;
- les notes horodatées et attribuées sur les immeubles, les biens, les pièces de colocation et les locataires ;
- les fiches locataires et leurs documents ;
- le back-office et ses droits d’accès.

Les chambres de colocation, baux, loyers et parcours des locataires seront ajoutés plus tard. Les écrans et indicateurs ne doivent pas anticiper ces données comme si elles existaient déjà.

## 2. Socle technique

| Sujet | Décision |
|---|---|
| Framework | Laravel 13, PHP 8.3 |
| Langue et fuseau | Français (fr), Europe/Paris |
| Base de données | SQLite, adaptée au faible volume et fichier monté depuis l’hôte |
| Déploiement | Docker Compose, conteneur php:8.3-apache |
| Image applicative | GitHub Actions publie l’image Docker dans GitHub Container Registry (GHCR) après chaque push sur master |
| Stockage des fichiers | Disque Laravel local, privé, persistant dans le volume storage_data |
| Carte | Leaflet avec fonds OpenStreetMap |

### Exécution locale et conteneurisée

- En local, le fichier SQLite est configuré par SQLITE_DATABASE_PATH.
- Le fichier Compose principal utilise l’image publiée dans GHCR via APP_IMAGE. Le fichier explicite `compose.local.yaml` ajoute uniquement le build local ; il ne doit pas être utilisé lors d’un déploiement depuis GHCR.
- Dans Docker, Compose monte ce fichier précisément dans /var/www/html/database/database.sqlite. Il ne faut pas remplacer ce montage par un volume Docker pour la base.
- Les médias sont conservés dans le volume Docker storage_data ; ils ne sont pas publiés via public/storage.
- Le workflow `.github/workflows/publish-image.yml` publie `ghcr.io/lfrugere/gestion_locative:latest` et un tag égal au SHA complet du commit. L’image ne contient ni fichier `.env`, ni base SQLite, ni médias ; Compose fournit ces données au démarrage.
- L’entrypoint Docker exécute `php artisan migrate --force`, puis `php artisan db:seed --force`, avant Apache. Le seeder est idempotent : les rôles et permissions sont retrouvés ou créés, et le compte administrateur existant par e-mail est mis à jour depuis `ADMIN_NAME` et `ADMIN_PASSWORD`.
- Les fichiers peuvent peser jusqu’à 20 Mo. PHP doit avoir upload_max_filesize = 20M et post_max_size = 24M ; le Dockerfile les applique déjà.
- La page /admin/configuration, réservée à l’administrateur, vérifie les limites PHP, les extensions requises par Laravel et SQLite, les répertoires storage d’archivage et de travail, le fuseau et la langue. Elle est une checklist de configuration, pas une page de métriques d’exploitation.

Ne pas ajouter un service MySQL, Redis, Elasticsearch ou un broker sans besoin métier explicite : cela compliquerait le déploiement et les sauvegardes sans bénéfice à cette échelle.

## 3. Organisation applicative

    app/
      Http/Controllers/Admin/  contrôleurs du back-office
      Models/                  modèles Eloquent et relations
      Services/                géocodage et gestion transactionnelle des médias
    resources/views/
      admin/                   vues Blade du back-office et fragments partagés
      layouts/admin.blade.php  layout et design system actuel
    database/
      migrations/              schéma SQLite
      seeders/                 rôles, permissions et administrateur initial
    tests/Feature/             parcours fonctionnels et autorisations

Les contrôleurs valident les entrées, appellent un service lorsque l’opération est transverse, puis redirigent avec un message. Les vues n’effectuent pas de logique de persistance. Les opérations sur fichiers et tags passent par MediaManager afin de rester transactionnelles et cohérentes.

## 4. Modèle métier et invariants

    ADDRESS 1 ─── 1 BUILDING
    BUILDING 1 ─── n PROPERTY
    ADDRESS  1 ─── 0..1 PROPERTY (maison uniquement)
    PROPERTY 1 ─── n PROPERTY_ROOM (colocation uniquement)
    BUILDING ou PROPERTY 1 ─── n MEDIA
    PROPERTY_ROOM 1 ─── n MEDIA
    USER     0..1 ─── 1 TENANT
    TENANT   1 ─── n MEDIA
    BUILDING, PROPERTY, PROPERTY_ROOM ou TENANT 1 ─── n NOTE
    MEDIA n ─── n TAG

| Entité | Règles à préserver |
|---|---|
| Immeuble | Possède toujours une adresse propre et une référence unique. Sa suppression est interdite tant que des biens y sont rattachés. |
| Appartement / parking | Doit être rattaché à un immeuble ; son adresse propre est absente et l’adresse de l’immeuble est utilisée. |
| Maison | N’est pas rattachée à un immeuble et possède sa propre adresse. |
| Bien | A une référence unique, un type (apartment, house, parking), un statut (active, inactive) et peut être marqué comme colocation uniquement s’il s’agit d’un appartement ou d’une maison. La colocation ne peut pas être désactivée tant que des pièces sont rattachées au bien. |
| Pièce de colocation | Appartient à un bien marqué comme colocation. Elle représente une chambre ou une pièce commune (salon, cuisine, salle de bain, WC, autre), avec surface optionnelle et statut. Elle ne porte aucun bail, loyer ni affectation de locataire. |
| Locataire | Dossier métier créé indépendamment d’un compte User. Il porte civilité, prénom, nom, date de naissance optionnelle et statut (candidate, validating, active, former, refused). Un compte User optionnel et unique pourra lui être rattaché pour le futur profil locataire ; aucun bail ni logement ne lui est encore rattaché. |
| Adresse | Contient latitude, longitude et date de géocodage quand le géocodage aboutit. |
| Média | Est polymorphiquement rattaché à un immeuble, un bien, une pièce de colocation ou un locataire. Une photo principale au plus est définie par propriétaire ; le locataire est limité à une photo d’identité. |
| Note | Est rattachée à un immeuble, un bien, une pièce de colocation ou un locataire. Porte un texte, l’auteur et la date de création, puis l’auteur et la date de dernière modification lorsqu’elle a été modifiée. Seuls son auteur ou le rôle admin peuvent la modifier ou la supprimer. |
| Tag | Est partagé entre les documents ; les tags sont synchronisés depuis une liste séparée par des virgules. |

Si un changement modifie l’une de ces relations, ajouter ou adapter un test fonctionnel. Ne pas uniquement masquer une incohérence dans l’interface.

## 5. Authentification et autorisations

L’authentification utilise Laravel Fortify. L’inscription publique est désactivée.

| Rôle | Droits actuels |
|---|---|
| admin | Accès au back-office et gestion complète des immeubles, biens et locataires |
| gestionnaire | Accès et consultation des immeubles et biens ; gestion complète des locataires |
| locataire | Rôle réservé aux évolutions futures |

Les permissions Spatie sont appliquées à deux niveaux : middleware de route et directives Blade @can. Les deux doivent rester alignés. Les médias suivent le droit de leur propriétaire : un fichier d’immeuble requiert le droit sur les immeubles, un fichier de bien celui sur les biens et un fichier de locataire celui sur les locataires. Les notes suivent la même règle pour être ajoutées ; les modifier ou les supprimer est en plus réservé à leur auteur ou au rôle admin, même pour un utilisateur qui dispose du droit de gérer l’entité porteuse.

La page de configuration requiert la permission manage system, attribuée au seul rôle admin.

## 6. Médias, documents et géocodage

### Médias

- Les photos acceptent JPG, PNG et WebP ; les documents acceptent PDF, images, DOC/DOCX et XLS/XLSX.
- La limite est de 20 Mo, contrôlée dans Laravel et PHP.
- La première photo devient automatiquement principale. La suppression de la photo principale promeut une autre photo lorsqu’elle existe.
- Un locataire possède au plus une photo d’identité. Cette règle est contrôlée dans l’interface et par le contrôleur ; les pièces jointes restent multiples.
- Les téléchargements passent exclusivement par la route admin.media.download, qui vérifie les droits avant de servir le fichier.
- Chaque média possède un kind technique (photo ou document) et un type métier (identity, bank_details, insurance, diagnostics ou other selon le propriétaire).
- Les photos sont stockées sous media/<type_proprietaire>/<reference_ou_ulid>/photos. Les documents sont stockés dans le répertoire correspondant à leur type.
- Les immeubles et biens utilisent leur référence comme identifiant de stockage ; les locataires utilisent une clé ULID technique stable, afin de ne pas exposer ni dépendre de leur identité civile dans les chemins.
- Les nouveaux fichiers utilisent un nom UUID et restent sur le disque Laravel privé. La modification du type déplace le fichier vers le répertoire correspondant.
- La carte médias affiche d’abord les pièces jointes et leur taille, puis les photos sous forme de galerie. Le visualiseur plein format permet de naviguer entre les photos.

### Géocodage

- Une adresse est géocodée après création ou mise à jour.
- La commande php artisan addresses:geocode permet de traiter les adresses existantes ; --force relance celles déjà géocodées.
- Une absence de coordonnées n’est pas bloquante : l’interface explique que la carte est indisponible et le tableau de bord le signale.
- Les appels externes de géocodage peuvent échouer. Ne jamais empêcher la création d’un immeuble ou d’une maison à cause de cette indisponibilité.

### Notes

- Chaque immeuble, bien, pièce de colocation et locataire peut recevoir un fil de notes horodatées, triées de la plus récente à la plus ancienne.
- Ajouter une note exige le même droit que gérer l’entité porteuse (manage buildings, manage properties ou manage tenants). Modifier ou supprimer une note est réservé à son auteur ou au rôle admin, même si l’utilisateur dispose par ailleurs du droit de gérer l’entité.
- Les notes remplacent l’ancien champ notes en texte libre des immeubles, des biens et des pièces de colocation ; son contenu existant a été migré en première note lors de l’introduction de ce système.

## 7. Design system du back-office

Le back-office est volontairement sobre, dense et lisible. Il n’utilise pas de bibliothèque CSS : les styles sont centralisés dans resources/views/layouts/admin.blade.php afin de conserver un langage visuel cohérent.

### Principes visuels

- Fond gris bleuté clair, navigation latérale bleu nuit, actions principales vert sarcelle.
- Typographie compacte : ne pas réintroduire de titres surdimensionnés ni d’espacements excessifs.
- Cartes blanches avec bordure et ombre discrète ; rayon de 12 à 16 px.
- Boutons primaires vert sarcelle, boutons secondaires gris clair, actions destructives rouges.
- Les actions de tableaux sont des boutons icônes, avec infobulle au survol et libellé accessible (aria-label).
- Les listes privilégient une information scannable : référence, nom, contexte et état.
- Les fiches utilisent un en-tête sombre pour l’identité de l’entité. Lorsqu’elle existe, la photo principale est placée dans ce bandeau, entre l’identité et les actions ; le panneau latéral reste dédié à la carte.
- Le statut d’un locataire réutilise status-pill : candidat violet, en validation ambre, actif vert, ancien ou refusé gris.
- Les formulaires d’ajout et d’édition de médias s’ouvrent dans des dialogues natifs ; ils ne doivent pas alourdir la fiche.
- Les photos sont des miniatures cliquables. Une boîte de dialogue permet la consultation plein format et la navigation clavier.

### Composants et classes à réutiliser

| Usage | Classes ou fragments |
|---|---|
| Layout | layouts.admin, admin-shell, admin-main, admin-header |
| Boutons | button, button secondary, button danger, icon-action |
| Fiches | detail-hero, detail-grid, detail-panel, detail-aside |
| États | status-pill, status-active, status-muted, flash, errors |
| Médias | admin._media, media-card, photo-grid, modal-dialog |
| Notes | admin._notes, notes-feed, note-entry |
| Carte | admin._map, map-card, address-map |

Avant d’ajouter une règle CSS, chercher une classe existante. Les styles spécifiques à une vue restent dans le layout tant que le projet n’a pas adopté une feuille de style compilée dédiée ; ne pas créer de styles inline isolés dans une vue.

Les points de rupture existants sont 980 px et 720 px. Toute nouvelle grille doit être lisible à ces tailles, sans nécessiter de défilement horizontal hors des tableaux.

## 8. Tableau de bord

La page /admin doit rester un outil de pilotage du périmètre existant :

- compteurs d’immeubles, de biens, de types de bien et de biens actifs ;
- actions rapides vers les créations ;
- points d’attention objectivement calculables (médias manquants, coordonnées absentes) ;
- derniers immeubles et biens avec accès direct aux fiches.

Ne pas y afficher de taux d’occupation, loyers, échéances de bail ou données de locataires avant que le modèle correspondant existe.

## 9. Conventions de modification

1. Partir de master pour toute nouvelle PR.
2. Limiter une PR à une évolution cohérente ; ne pas mélanger une refonte visuelle avec une migration métier non liée.
3. Préserver l’UTF-8 dans les vues et documents français.
4. Utiliser des migrations pour chaque changement de schéma ; ne jamais modifier une migration déjà appliquée.
5. Ajouter des tests de feature pour les règles métier, les permissions, les formulaires et les régressions corrigées.
6. Pour une vue ou le layout, exécuter php artisan view:cache ; pour le PHP, lancer vendor/bin/pint --dirty ; toujours terminer par php artisan test et git diff --check.
7. Documenter dans ce fichier toute décision durable qui influence les futurs écrans ou modèles.

## 10. Évolutions prévues et limites assumées

| Sujet | État | Décision |
|---|---|---|
| SQLite | Acceptable | Suffisant pour le faible nombre de biens et utilisateurs. Réévaluer seulement en cas d’accès concurrents soutenus ou de besoins de reporting complexes. |
| Fichiers locaux | À surveiller | Sauvegarder le fichier SQLite et le volume storage_data ensemble ; ils constituent une même unité de restauration. |
| Carte et géocodage | À surveiller | Dépendance à des services publics externes ; conserver les coordonnées en base et proposer le rejeu par commande. |
| Accès locataire | À concevoir | Le dossier Tenant ne crée pas automatiquement un compte User. Un compte peut être rattaché à un seul dossier, puis les futures routes locataires devront séparer strictement leurs données de l’administration. |
| Conservation des dossiers | À concevoir avec les baux | Les dossiers ancien ou refusé deviendront éligibles à la suppression après deux ans sans contrat actif. Tant que les baux ne sont pas modélisés, aucune tâche planifiée ne doit supprimer ces données. status_changed_at conserve le point de départ du futur calcul. |
| Baux et parcours | À concevoir | Introduire les nouveaux modèles avant les tableaux de bord financiers ou d’occupation. La colocation et ses pièces restent pour l’instant un inventaire du bien ; elles ne créent pas encore d’occupants, de baux ou de loyers partagés. |
