# Rôles et permissions

Ce document recense, entité par entité, qui a le droit de faire quoi dans le back-office. Il complète [architecture.md](architecture.md) §5, qui décrit le mécanisme général (Spatie, middleware de route + directives Blade). Toute évolution des droits doit être répercutée ici, dans `database/seeders/DatabaseSeeder.php` et dans un test fonctionnel.

## Rôles

| Rôle | Attribution |
|---|---|
| `admin` | Compte créé depuis `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` (seeder). Accès complet à toutes les entités. |
| `gestionnaire` | Compte créé depuis `MANAGER_NAME` / `MANAGER_EMAIL` / `MANAGER_PASSWORD` (seeder), ou attribué à un utilisateur via le back-office. Accès en consultation à l'ensemble du patrimoine, et en écriture limité aux biens dont il est explicitement gestionnaire (table `property_manager`). |
| `locataire` | Réservé aux évolutions futures (accès locataire). N'a aujourd'hui que la permission `access admin`, ce qui lui permet de se connecter et d'atteindre `/mes-contrats` ; aucune autre route ne lui est ouverte. |

Un même compte ne doit porter qu'un seul de ces rôles à la fois. Le seeder crée deux comptes distincts (`ADMIN_*` et `MANAGER_*`) plutôt qu'un compte cumulant les rôles admin et gestionnaire.

## Permissions Spatie déclarées

Définies et synchronisées dans `database/seeders/DatabaseSeeder.php`.

| Permission | admin | gestionnaire | locataire |
|---|---|---|---|
| `access admin` | ✅ | ✅ | ✅ |
| `view buildings` | ✅ | ✅ | – |
| `manage buildings` | ✅ | – | – |
| `view properties` | ✅ | ✅ | – |
| `manage properties` | ✅ | – | – |
| `view tenants` | ✅ | ✅ | – |
| `manage tenants` | ✅ | – | – |
| `view bank accounts` | ✅ | ✅ | – |
| `manage bank accounts` | ✅ | – | – |
| `manage invoices` | ✅ | ✅ | – |
| `manage notes` | ✅ | ✅ | – |
| `manage system` | ✅ | – | – |
| `manage users` | ✅ | – | – |

Ces permissions sont vérifiées à deux niveaux, qui doivent rester alignés : le middleware `permission:...` sur chaque route (`routes/web.php`) et les directives Blade `@can` / `@canany` dans les vues. Les menus de la barre latérale utilisent en plus un contrôle par rôle (`@hasrole` / `@hasanyrole` dans `resources/views/layouts/admin.blade.php`) pour décider quel menu afficher.

Une permission accordée au niveau de la route n'ouvre pas forcément un droit sans restriction : plusieurs entités appliquent en plus un contrôle de propriété (« ownership »), détaillé ci-dessous.

## Contrôle de propriété (gestionnaire ↔ bien)

Un bien peut être mis « en gestion » pour un ou plusieurs comptes `gestionnaire` (relation `Property::managers()`, gérée par un admin via `properties.managers.update`). `Property::isManagedBy(User $user)` centralise ce test.

Sur un bien donné, un utilisateur est autorisé à agir dès lors que :

```
$user->hasRole('admin') || $property->isManagedBy($user)
```

Ce contrôle s'ajoute — il ne remplace pas — la permission Spatie de base. Il est appliqué dans :

- `InvoiceController::store` / `destroy` (`canManageInvoicesFor`)
- `MediaController::storeInvoice`, et `canManage()` / `canView()` pour les médias attachés à une facture (`canManageInvoice`)
- `MediaController::storeProperty`, et `canManage()` pour les médias attachés à un bien (`canManageProperty`)
- `NoteController::storeProperty`
- `PortfolioController::showProperty` (accès en lecture à `/mes-biens/{property}`)

Les vues correspondantes (`admin/properties/show.blade.php`, `menus/mes-biens-show.blade.php`) calculent ce même booléen (`$isPropertyManager`) pour n'afficher les formulaires d'ajout que lorsque l'action sera effectivement autorisée côté serveur.

Ce contrôle de propriété ne s'applique **pas** aux pièces de colocation (`PropertyRoom`) ni à l'édition du bien lui-même (fiche, immeuble de rattachement, suppression) : ces actions restent réservées à `manage properties`, donc au seul rôle `admin`.

## Détail par entité

### Immeubles (buildings)

| Action | admin | gestionnaire |
|---|---|---|
| Consulter la liste et une fiche | ✅ (`view buildings`) | ✅ (`view buildings`) |
| Créer / modifier / supprimer | ✅ (`manage buildings`) | ❌ |
| Ajouter une photo / pièce jointe | ✅ | ❌ |
| Ajouter une note | ✅ (`manage notes`) | ✅ (`manage notes`, non scopé — tout gestionnaire peut noter n'importe quel immeuble) |

### Biens (properties)

| Action | admin | gestionnaire |
|---|---|---|
| Consulter la liste et une fiche (`/properties`) | ✅ | ✅ (`view properties`, tous les biens, pas seulement les siens) |
| Consulter « Mes biens » (`/mes-biens`) | — (accès à tous les biens via `/properties`) | ✅, restreint aux biens dont il est gestionnaire |
| Créer / modifier / supprimer un bien, gérer les pièces de colocation, attribuer un gestionnaire | ✅ (`manage properties`) | ❌ |
| Ajouter / modifier / supprimer une photo ou pièce jointe | ✅ | ✅, uniquement sur un bien dont il est gestionnaire |
| Ajouter une note | ✅ (`manage notes`) | ✅, uniquement sur un bien dont il est gestionnaire |
| Ajouter / supprimer une facture, gérer ses pièces jointes | ✅ (`manage invoices`) | ✅, uniquement sur un bien dont il est gestionnaire |
| Voir le compte bancaire rattaché | ✅ | ❌ (nécessite `manage properties`) |

### Pièces de colocation (property rooms)

| Action | admin | gestionnaire |
|---|---|---|
| Consulter | ✅ (`view properties`) | ✅ (`view properties`) |
| Créer / modifier / supprimer, gérer les médias | ✅ (`manage properties`) | ❌ |
| Ajouter une note | ✅ (`manage notes`) | ✅ (`manage notes`, non scopé) |

### Locataires (tenants)

| Action | admin | gestionnaire |
|---|---|---|
| Consulter la liste et une fiche | ✅ | ✅ (`view tenants`) |
| Créer / modifier / supprimer, gérer les médias | ✅ (`manage tenants`) | ❌ (lecture seule) |
| Ajouter une note | ✅ (`manage notes`) | ✅ (`manage notes`, non scopé) |

### Comptes bancaires

| Action | admin | gestionnaire |
|---|---|---|
| Consulter | ✅ | ✅ (`view bank accounts`) |
| Créer / modifier / supprimer, saisir des écritures, rapprochements | ✅ (`manage bank accounts`) | ❌ |

### Notes (règle transverse)

- Ajouter une note sur une entité exige `manage notes`. Pour les biens uniquement, ce droit est en plus restreint aux biens gérés (voir ci-dessus) ; pour les immeubles, pièces et locataires, `manage notes` suffit sans contrôle de propriété supplémentaire.
- Modifier ou supprimer une note est réservé à son auteur ou au rôle `admin`, même si l'utilisateur dispose par ailleurs du droit `manage notes` (`NoteController::canModify`).

### Configuration système et utilisateurs

| Action | admin | gestionnaire |
|---|---|---|
| Page `/configuration` (checklist système) | ✅ (`manage system`) | ❌ |
| Gestion des comptes utilisateurs (`/users`) | ✅ (`manage users`) | ❌ |

### Menus de la barre latérale

| Menu | Condition d'affichage |
|---|---|
| Gestion Locative | rôle `gestionnaire` ou `locataire` |
| Admin Locative | rôle `admin` |
| Admin Général | permission `manage system` ou `manage users` |

## Écarts connus, non traités par ce document

- Les notes sur immeubles, pièces de colocation et locataires ne sont pas scopées par gestionnaire : un compte `gestionnaire` peut aujourd'hui ajouter une note sur n'importe quel immeuble ou locataire, pas seulement sur ceux qu'il gère. Seules les notes sur les biens ont été scopées (cf. section ci-dessus).
- Le rôle `gestionnaire` ne peut ni créer ni modifier une fiche locataire (lecture seule), alors qu'une ancienne version de ce document affirmait le contraire ; c'est le comportement actuellement testé (`test_admin_can_manage_tenant_media_and_manager_has_read_only_access`) et volontaire tel que codé, mais mérite d'être confirmé comme choix produit.
