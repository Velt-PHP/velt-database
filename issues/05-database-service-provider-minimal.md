# Issue 05 - Ajouter DatabaseServiceProvider minimal

## Labels

`module:1-foundations`, `area:database`, `area:kernel`, `type:feature`, `priority:p1`, `status:ready`

## Objectif

Permettre au package database de s'enregistrer dans l'application via le systeme de service providers du kernel.

## Travail attendu

- Creer `DatabaseServiceProvider`.
- Enregistrer `DatabaseManager` dans le container.
- Lire la configuration via `ConfigRepositoryInterface`.
- Ne pas ouvrir la connexion PDO tant qu'elle n'est pas demandee si possible.
- Documenter comment des tests peuvent utiliser SQLite en memoire.

## Criteres d'acceptation

- Le provider enregistre le manager sans dependance circulaire.
- Une app avec config SQLite peut resoudre `DatabaseManager`.
- Une app sans config database obtient une erreur claire seulement au moment d'utiliser database.

## Definition of Done

- Provider implemente.
- Tests avec container fake ou kernel reel.
- Documentation d'integration ajoutee.

