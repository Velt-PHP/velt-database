# Issue 04 - Integrer database avec configuration kernel

## Labels

`module:1-foundations`, `area:database`, `area:kernel`, `type:feature`, `priority:p0`, `status:ready`

## Objectif

Permettre a la couche database de lire sa configuration depuis le kernel sans couplage fort.

## Pourquoi cette issue est obligatoire

Une connexion PDO hardcodee ou configuree manuellement dans chaque test ne suffit pas pour un framework. Database doit pouvoir consommer une configuration standard exposee par le kernel.

## Travail attendu

- Accepter une implementation de `ConfigRepositoryInterface`.
- Lire `database.default`.
- Lire `database.connections.{name}`.
- Supporter SQLite en memoire pour les tests.
- Ajouter erreurs claires pour configuration manquante.

## Contraintes

- Ne pas faire dependre le kernel de database.
- Ne pas lire `.env` directement dans database.
- Ne pas cacher silencieusement les erreurs de configuration.

## Criteres d'acceptation

- Une connexion SQLite peut etre creee depuis une config kernel.
- Une connexion MySQL peut generer le DSN attendu.
- Une connexion PostgreSQL peut generer le DSN attendu.
- Une configuration absente lance une exception explicite.
- Les tests n'ont pas besoin de vraie base MySQL/PostgreSQL.

## Definition of Done

- Integration config implemente.
- Tests avec repository de configuration factice.
- Documentation de la structure `config/database.php`.

