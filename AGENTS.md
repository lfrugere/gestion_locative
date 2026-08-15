# Guide pour les agents IA

Avant toute modification, lire [docs/architecture.md](docs/architecture.md). Ce document est la source de vérité pour les choix techniques, métier et visuels du projet.

## Règles de travail

- Préserver les règles métier et les ACL existantes ; ne pas les contourner dans les vues.
- Réutiliser les composants Blade et les classes CSS existantes avant d’en créer de nouveaux.
- Toute évolution qui introduit une règle métier, un rôle, une dépendance, une table ou un motif visuel doit mettre à jour docs/architecture.md.
- Les fichiers restent privés par défaut : ne jamais exposer le disque local ou les médias par un lien public.
- Utiliser SQLite. Ne pas introduire de serveur de base de données sans décision explicite.
- Valider les changements avec php artisan test, et avec php artisan view:cache lorsqu’une vue Blade ou le layout est modifié.

## Portée actuelle

Le projet gère le patrimoine (immeubles, appartements, maisons et parkings) et les fiches locataires. Les baux, loyers, chambres et parcours des locataires ne sont pas encore modélisés : ne pas créer d’indicateur ni de comportement qui les suppose.
