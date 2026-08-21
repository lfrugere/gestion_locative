# Rôles et permissions

Ce document recense, entité par entité, qui a le droit de faire quoi dans le back-office. Il complète [architecture.md](architecture.md) §5, qui décrit le mécanisme général (Spatie, middleware de route + directives Blade). Toute évolution des droits doit être répercutée ici, dans `database/seeders/DatabaseSeeder.php` et dans un test fonctionnel.

## Rôles

| Rôle | Attribution |
|---|---|
| `admin` | Compte créé depuis `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` (seeder). Accès complet à toutes les entités, y compris les pages `/buildings`, `/tenants`, `/bank-accounts`. **Exception : aucun accès aux médias (photos, pièces jointes), au fil de notes, ni aux factures, sur aucune entité** — décision produit volontaire, voir section dédiée ci-dessous. |
| `gestionnaire` | Compte créé depuis `MANAGER_NAME` / `MANAGER_EMAIL` / `MANAGER_PASSWORD` (seeder), ou attribué à un utilisateur via le back-office. Accès en écriture limité aux biens dont il est explicitement gestionnaire (table `property_manager`). Pour les immeubles, locataires et comptes bancaires, il n'a plus qu'un accès en **lecture seule**, via des pages dédiées scopées à ses biens gérés (voir ci-dessous) — les pages admin `/buildings`, `/tenants`, `/bank-accounts` lui sont strictement interdites, y compris par URL directe. |
| `locataire` | Réservé aux évolutions futures (accès locataire). N'a aujourd'hui que la permission `access admin`, ce qui lui permet de se connecter et d'atteindre `/mes-contrats` ; aucune autre route ne lui est ouverte. |

Un même compte ne doit porter qu'un seul de ces rôles à la fois. Le seeder crée deux comptes distincts (`ADMIN_*` et `MANAGER_*`) plutôt qu'un compte cumulant les rôles admin et gestionnaire.

## Médias, notes et factures : le rôle admin en est totalement exclu

Décision produit volontaire (pas une régression) : le rôle `admin` n'a **aucun accès**, ni en lecture ni en écriture, aux médias (photos, pièces jointes — y compris les pièces jointes de facture), au fil de notes, et aux **factures**, sur **aucune** entité (immeubles, biens, pièces de colocation, locataires). Les sections correspondantes sont entièrement masquées dans les vues pour un admin, et les routes sont bloquées côté serveur même par URL directe.

Mécanisme : `MediaController`, `NoteController` et `InvoiceController` n'ont plus de bypass `$user->hasRole('admin') ||` dans leurs contrôles d'autorisation — `canView()`/`canManage()` (médias) excluent explicitement l'admin en tête de fonction, et les contrôles d'ownership (`isManagedBy`) sont utilisés seuls, ce qui exclut l'admin de facto puisqu'il n'est jamais rattaché comme gestionnaire d'un bien. `NoteController::canModify` n'autorise plus non plus l'admin à modifier/supprimer la note d'un autre auteur. `InvoiceController::canManageInvoicesFor` suit le même principe.

**Conséquence assumée** : certaines fonctionnalités deviennent inatteignables par personne, faute d'un rôle qui en ait le droit :
- Photos et pièces jointes sur un **immeuble** (`manage buildings`, permission admin only).
- Photos sur une **pièce de colocation** (`manage properties`, permission admin only).
- Photos, pièce d'identité et documents sur un **locataire** (le gestionnaire n'a plus `manage tenants` non plus, cf. section Locataires).
- Notes sur un **immeuble** ou un **locataire**.

Sur les **biens** — médias, pièces jointes de facture, notes, et **factures elles-mêmes** (création, suppression, tags, écriture bancaire automatique) — la fonctionnalité reste utilisable par le gestionnaire, scopée à ses biens gérés ; seul l'admin en est exclu. C'est le seul cas où médias/notes/factures restent opérationnels dans l'application.

## Pages admin vs. pages gestionnaire (immeubles, locataires, comptes bancaires)

Un menu caché côté sidebar (`@hasrole`/`@hasanyrole` dans `resources/views/layouts/admin.blade.php`) n'est **pas** un contrôle d'accès : il ne fait qu'organiser l'affichage. Les routes elles-mêmes doivent porter le contrôle. Pour ces trois entités, deux jeux de pages distincts et strictement séparés existent désormais :

| | Pages admin | Pages gestionnaire |
|---|---|---|
| Immeubles | `/buildings`, `/buildings/{id}`, création/édition/suppression | `/mes-immeubles`, `/mes-immeubles/{id}` (lecture seule) |
| Locataires | `/tenants`, `/tenants/{id}`, création/édition/suppression | `/mes-locataires`, `/mes-locataires/{id}` (lecture seule) |
| Comptes bancaires (compte lui-même : CRUD) | `/bank-accounts`, `/bank-accounts/{id}`, création/édition/suppression du compte | ❌ aucune page équivalente : le gestionnaire ne crée ni ne modifie un compte |
| Comptes bancaires (écritures, rapprochements) | mêmes routes `bank-accounts.transactions.*` / `bank-accounts.reconciliations.*` | mêmes routes, ouvertes au gestionnaire, scopées par compte géré ; consultées depuis `/mes-comptes-bancaires/{id}` |

Toutes les routes `/buildings*`, `/tenants*`, ainsi que le listing/la fiche/le CRUD du compte bancaire lui-même sous `/bank-accounts*` (création, édition, suppression du compte), portent le middleware **`role:admin`** en plus du middleware `permission:...` existant (`routes/web.php`) :

```php
Route::middleware(['role:admin', 'permission:view buildings'])->group(function () { ... });
Route::middleware(['role:admin', 'permission:manage buildings'])->group(function () { ... });
// idem pour tenants (view/manage) et pour le CRUD du compte bancaire lui-même
// (bank-accounts.index/show/create/store/edit/update/destroy)
// ainsi que pour buildings.notes.store et tenants.notes.store
```

**Choix retenu et justification** : `role:admin` a été préféré à un simple retrait des permissions `view buildings` / `view tenants` / `manage tenants` / `view bank accounts` du rôle `gestionnaire`, parce qu'il verrouille la route de façon positive et explicite (« ces pages n'appartiennent qu'à l'admin ») plutôt que négative (« le gestionnaire n'a pas la permission, pour l'instant ») : un futur ajout de permission à un rôle ne peut plus rouvrir ces pages par erreur. Ces permissions ont malgré tout été retirées du rôle `gestionnaire` dans le seeder (hygiène : elles ne servent plus à rien pour lui, ses pages dédiées ne les vérifient pas), les deux mécanismes se recoupant en défense en profondeur.

**Exception pour les comptes bancaires** : contrairement aux immeubles et locataires, la saisie d'écritures et les rapprochements (`bank-accounts.transactions.*`, `bank-accounts.reconciliations.*`) ne portent **pas** `role:admin` — seulement `permission:manage bank accounts`, que le gestionnaire possède à nouveau. L'ownership est vérifié au niveau du contrôleur (`BankAccountController::canManage`, `BankTransactionController`, `BankReconciliationController` — `$user->hasRole('admin') || $bankAccount->isManagedBy($user)`), pas au niveau de la route : un gestionnaire peut donc saisir une écriture ou démarrer/clôturer un rapprochement sur un compte lié à l'un de ses biens gérés, directement depuis `/mes-comptes-bancaires/{id}` (qui réutilise ces mêmes routes de traitement), mais reçoit un 403 sur un compte qu'il ne gère pas. La création/modification/suppression du compte bancaire lui-même (IBAN, gestionnaire rattaché, etc.) reste strictement admin only : il n'y a pas de bien pour établir un scope au moment de la création d'un compte, et modifier ces attributs reste jugé sensible.

Les pages gestionnaire (`PortfolioController::myBuildings`/`showBuilding`, `myTenants`/`showTenant`, `myBankAccounts`/`showBankAccount`) sont routées sous le middleware `role:gestionnaire` (`routes/web.php`) et scopent systématiquement leurs requêtes aux biens gérés par l'utilisateur connecté (`whereHas('properties', fn ($q) => $q->whereHas('managers', ...))`), avec un `abort_unless(..., 403)` sur les fiches individuelles si l'entité n'est pas rattachée à un bien géré. Elles ne proposent aucun bouton créer/modifier/supprimer, ni formulaire d'ajout de note ou de pièce jointe.

## Permissions Spatie déclarées

Définies et synchronisées dans `database/seeders/DatabaseSeeder.php`.

| Permission | admin | gestionnaire | locataire |
|---|---|---|---|
| `access admin` | ✅ | ✅ | ✅ |
| `view buildings` | ✅ | – | – |
| `manage buildings` | ✅ | – | – |
| `view properties` | ✅ | ✅ | – |
| `manage properties` | ✅ | – | – |
| `view tenants` | ✅ | – | – |
| `manage tenants` | ✅ | – | – |
| `view bank accounts` | ✅ | – | – |
| `manage bank accounts` | ✅ | ✅ (scopé : écritures/rapprochements uniquement, pas le CRUD du compte, verrouillé par `role:admin`) | – |
| `manage invoices` | ✅ | ✅ (scopé) | – |
| `manage notes` | ✅ | ✅ (scopé) | – |
| `manage system` | ✅ | – | – |
| `manage users` | ✅ | – | – |

Le gestionnaire n'a donc plus aucune des permissions Spatie relatives aux immeubles, locataires et comptes bancaires : ses pages dédiées (`/mes-immeubles`, `/mes-locataires`, `/mes-comptes-bancaires`) ne les vérifient pas, elles s'appuient uniquement sur le rôle (`role:gestionnaire`) et le scope par bien géré.

Ces permissions sont vérifiées à deux niveaux, qui doivent rester alignés : le middleware `permission:...` (et, pour buildings/tenants/bank-accounts, `role:admin`) sur chaque route (`routes/web.php`) et les directives Blade `@can` / `@canany` dans les vues. Les menus de la barre latérale utilisent en plus un contrôle par rôle (`@hasrole` / `@hasanyrole` dans `resources/views/layouts/admin.blade.php`) pour décider quel menu afficher — mais ce contrôle de menu, comme rappelé plus haut, n'est jamais le seul rempart : la route est toujours protégée indépendamment.

## Contrôle de propriété (gestionnaire ↔ bien)

Un bien peut être mis « en gestion » pour un ou plusieurs comptes `gestionnaire` (relation `Property::managers()`, gérée par un admin via `properties.managers.update`). `Property::isManagedBy(User $user)` centralise ce test :

```
$user->hasRole('admin') || $property->isManagedBy($user)
```

D'autres entités exposent le même type de méthode, qui délègue au bien géré le plus proche :

- `Building::isManagedBy(User $user)` — vrai si au moins un bien de l'immeuble est géré par l'utilisateur.
- `BankAccount::isManagedBy(User $user)` — vrai si au moins un bien rattaché à ce compte (`BankAccount::properties()`, l'inverse de `Property::bankAccount()`) est géré par l'utilisateur.
- `Tenant::isManagedBy(User $user)` — vrai si le locataire est rattaché (table pivot `property_tenant`, via `Tenant::properties()` / `Property::tenants()`) à au moins un bien géré par l'utilisateur.

Ce contrôle est appliqué dans :

- `PortfolioController::myBuildings` (liste filtrée), `showBuilding` (403 si aucun bien géré dans l'immeuble)
- `PortfolioController::myBankAccounts` (liste filtrée), `showBankAccount` (403 si le compte n'est rattaché à aucun bien géré)
- `PortfolioController::myTenants` (liste filtrée), `showTenant` (403 si le locataire n'est rattaché à aucun bien géré)
- `PortfolioController::showProperty` (accès en lecture à `/mes-biens/{property}`)
- `MediaController::canView` pour le téléchargement de photos/pièces jointes des immeubles et locataires (utilisé par les pages `/mes-immeubles/{id}` et `/mes-locataires/{id}`)
- `MediaController::storeProperty`, et `canManage()` / `canView()` pour les médias attachés à une facture (`canManageInvoice`)
- `MediaController::storeProperty`, et `canManage()` pour les médias attachés à un bien (`canManageProperty`)
- `NoteController::storeProperty`, `storePropertyRoom`
- `InvoiceController::store` / `destroy` (`canManageInvoicesFor`)

Les vues correspondantes (`admin/properties/show.blade.php`, `menus/mes-immeubles-show.blade.php`, `menus/mes-comptes-bancaires-show.blade.php`, `menus/mes-locataires-show.blade.php`, `menus/mes-biens-show.blade.php`) calculent ce même type de booléen pour n'afficher les actions que lorsqu'elles seront effectivement autorisées côté serveur — étant entendu que les trois nouvelles pages gestionnaire n'affichent de toute façon aucune action d'écriture.

Ce contrôle de propriété ne s'applique **pas** aux pièces de colocation (`PropertyRoom`, hors notes) ni à l'édition du bien lui-même (fiche, immeuble de rattachement, suppression), ni à l'immeuble ou au compte bancaire eux-mêmes : ces actions restent réservées à `admin` (accès aux routes verrouillé par `role:admin`).

## Détail par entité

### Immeubles (buildings)

| Action | admin | gestionnaire |
|---|---|---|
| Consulter la liste et une fiche via `/buildings` | ✅ | ❌ (403, `role:admin`) |
| Consulter la liste et une fiche via `/mes-immeubles` (lecture seule) | — (page réservée au rôle gestionnaire) | ✅, **scopé** : uniquement les immeubles ayant au moins un bien qu'il gère |
| Créer / modifier / supprimer | ✅ (`manage buildings`, `role:admin`) | ❌ |
| Ajouter une photo / pièce jointe | ✅ | ❌ |
| Ajouter une note | ✅ (`manage notes`, `role:admin`) | ❌ (page en lecture seule ; retiré par rapport à une itération précédente, voir « Notes » ci-dessous) |

### Biens (properties)

| Action | admin | gestionnaire |
|---|---|---|
| Consulter la liste et une fiche (`/properties`) | ✅ | ✅ (`view properties`, tous les biens, pas seulement les siens — non scopé, comportement inchangé) |
| Consulter « Mes biens » (`/mes-biens`) | — (accès à tous les biens via `/properties`) | ✅, restreint aux biens dont il est gestionnaire |
| Créer / modifier / supprimer un bien, gérer les pièces de colocation, attribuer un gestionnaire | ✅ (`manage properties`) | ❌ |
| Ajouter / modifier / supprimer une photo ou pièce jointe | ✅ | ✅, uniquement sur un bien dont il est gestionnaire |
| Ajouter une note | ✅ (`manage notes`) | ✅, uniquement sur un bien dont il est gestionnaire |
| Ajouter / supprimer une facture, gérer ses pièces jointes | ❌ (aucun accès, cf. section « Médias, notes et factures ») | ✅, uniquement sur un bien dont il est gestionnaire |
| Voir le compte bancaire rattaché | ✅ (lien vers `/bank-accounts/{id}`) | ✅ (lien vers `/mes-comptes-bancaires/{id}`), uniquement sur un bien dont il est gestionnaire |

### Pièces de colocation (property rooms)

| Action | admin | gestionnaire |
|---|---|---|
| Consulter | ✅ (`view properties`) | ✅ (`view properties`) |
| Créer / modifier / supprimer, gérer les médias | ✅ (`manage properties`) | ❌ |
| Ajouter une note | ✅ (`manage notes`) | ✅ (`manage notes`), **scopé** : uniquement s'il gère le bien parent de la pièce |

### Locataires (tenants)

Un locataire peut être rattaché à un ou plusieurs biens (table pivot `property_tenant`, `Tenant::properties()` / `Property::tenants()`).

| Action | admin | gestionnaire |
|---|---|---|
| Consulter la liste et une fiche via `/tenants` | ✅ | ❌ (403, `role:admin`) |
| Consulter la liste et une fiche via `/mes-locataires` (lecture seule) | — (page réservée au rôle gestionnaire) | ✅, **scopé** : uniquement les locataires rattachés à au moins un bien qu'il gère |
| Créer / modifier / supprimer, gérer les médias | ✅ (`manage tenants`, `role:admin`) | ❌ |
| Ajouter une note | ✅ (`manage notes`, `role:admin`) | ❌ (page en lecture seule) |

### Comptes bancaires

| Action | admin | gestionnaire |
|---|---|---|
| Consulter la liste et une fiche via `/bank-accounts` | ✅ | ❌ (403, `role:admin`) |
| Consulter la liste et une fiche via `/mes-comptes-bancaires` (lecture seule) | — (page réservée au rôle gestionnaire) | ✅, **scopé** : uniquement les comptes rattachés à au moins un bien qu'il gère |
| Saisir une écriture, démarrer/pointer/clôturer/supprimer un rapprochement (depuis `/mes-comptes-bancaires/{id}`) | ✅ | ✅, **scopé** : uniquement sur un compte rattaché à au moins un bien qu'il gère (`manage bank accounts`, sans `role:admin`) |
| Créer / modifier / supprimer le compte lui-même (IBAN, gestionnaire rattaché…) | ✅ | ❌ (`role:admin`) |

### Notes (règle transverse)

- Ajouter une note sur une entité exige `manage notes`. Pour les biens et les pièces de colocation, ce droit reste ouvert au gestionnaire et scopé aux biens gérés (directement, ou via le bien parent / le bien de rattachement).
- Pour les **immeubles** et les **locataires**, l'ajout de note est désormais réservé à l'admin (`role:admin` sur `buildings.notes.store` et `tenants.notes.store`) : ces deux pages gestionnaire (`/mes-immeubles`, `/mes-locataires`) sont volontairement en lecture seule, y compris pour les notes, par cohérence avec le reste de la page — un gestionnaire souhaitant signaler une information sur un immeuble ou un locataire passe par une note sur le bien concerné (`/mes-biens/{id}`), qui reste ouverte.
- Modifier ou supprimer une note est réservé à son auteur ou au rôle `admin`, même si l'utilisateur dispose par ailleurs du droit `manage notes` (`NoteController::canModify`).

### Configuration système et utilisateurs

| Action | admin | gestionnaire |
|---|---|---|
| Page `/configuration` (checklist système) | ✅ (`manage system`) | ❌ |
| Gestion des comptes utilisateurs (`/users`) | ✅ (`manage users`) | ❌ |

### Menus de la barre latérale

| Menu | Condition d'affichage |
|---|---|
| Gestion Locative | rôle `gestionnaire` ou `locataire` — sous-liens « Mes immeubles », « Mes locataires », « Mes comptes bancaires » réservés au rôle `gestionnaire` |
| Admin Locative | rôle `admin` |
| Admin Général | permission `manage system` ou `manage users` |

Pour rappel : le menu ne fait qu'organiser l'affichage, il ne protège aucune route. Les routes `/buildings`, `/tenants`, `/bank-accounts` restent inaccessibles au gestionnaire même en tapant l'URL directement, grâce au middleware `role:admin` (voir `tests/Feature/RoleScopingTest.php`).

## Écart assumé : création d'un compte bancaire ex nihilo

La création d'un compte bancaire (`/bank-accounts/create`) reste réservée à `admin`, comme la totalité des pages `/bank-accounts*` désormais. Un gestionnaire souhaitant un nouveau compte bancaire doit le faire créer par un admin, puis se le faire rattacher à l'un de ses biens, après quoi il pourra le consulter (en lecture seule) sur `/mes-comptes-bancaires`.
