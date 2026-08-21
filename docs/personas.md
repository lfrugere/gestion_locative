# Personas

Ce document décrit les trois profils d'utilisateurs de l'application : qui ils sont, ce qu'ils viennent y faire, et le périmètre de leur responsabilité. Il précède et motive [roles-permissions.md](roles-permissions.md), qui traduit ces personas en droits techniques (permissions Spatie, scoping par bien géré) et documente l'état réellement codé aujourd'hui. Ce fichier-ci décrit l'intention produit ; il inclut donc des responsabilités qui ne sont pas encore implémentées (parcours locataire, désignation d'un locataire sur un bien, suivi des paiements) — voir la section « Écart avec l'existant » de chaque persona.

## Administrateur

**Qui il est** : profil technique / informatique. Ce n'est pas un exploitant du patrimoine au quotidien, mais celui qui configure et outille l'application pour les gestionnaires.

**Sa mission** :
- Créer les comptes utilisateurs et leur attribuer un rôle (admin, gestionnaire, locataire).
- Créer le patrimoine de référence : immeubles, appartements, maisons, parkings.
- Désigner, pour chaque bien, quel(s) gestionnaire(s) en ont la charge.
- Créer et configurer les comptes bancaires.

L'administrateur pose le cadre ; il n'est pas censé effectuer l'exploitation courante d'un bien (notes, factures, photos) — c'est le rôle du gestionnaire.

**Écart avec l'existant** : conforme au code actuel (`manage buildings`, `manage properties`, `manage bank accounts`, `manage users`, attribution des gestionnaires via `properties.managers.update`).

## Gestionnaire

**Qui il est** : le profil principal et le plus fréquent de l'application — celui qui exploite le patrimoine au jour le jour.

**Sa mission**, sur chacun des biens qui lui sont attribués :
- Tenir à jour les caractéristiques du bien (surface, étage, statut, etc.).
- Ajouter des notes de suivi.
- Ajouter des pièces jointes et des photos, y compris en changer.
- Saisir les factures liées au bien.
- Choisir et affecter le locataire en place sur le bien (à venir avec le parcours locataire).

Le gestionnaire n'intervient que sur les biens dont il a la charge, jamais sur l'ensemble du patrimoine : il n'a pas vocation à créer un immeuble, créer un bien, ou décider qui gère quoi — ce sont des actes de configuration réservés à l'administrateur.

**Écart avec l'existant** : la gestion du bien (notes, pièces jointes, photos, factures) est scopée au bien géré, conformément à cette description. La désignation du locataire en place sur un bien n'est pas encore possible : le modèle Tenant existe, mais rien ne le rattache aujourd'hui à un bien, une chambre ou un parking (voir architecture.md, section « Baux et parcours », À concevoir).

## Locataire

**Qui il est** : l'occupant d'un bien ou d'une chambre, avec un accès en lecture seule à ses propres informations.

**Sa mission** : consulter, sans rien modifier :
- sa propre fiche ;
- le bien qui lui est rattaché (appartement, maison), ou la chambre en cas de colocation, ou le parking ;
- s'il est à jour de ses paiements.

**Écart avec l'existant** : ce persona n'est aujourd'hui outillé que jusqu'à la connexion (`access admin` + accès à `/mes-contrats`, un écran encore vide). Il n'existe ni rattachement locataire ↔ bien/chambre/parking, ni suivi des paiements, ni fiche consultable. C'est le chantier « Accès locataire » et « Baux et parcours » d'architecture.md, section 10.
