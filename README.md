# Sous-module 05 - Database PDO

## Mission

Ce sous-module fournit une couche database simple pour le MVP. Il ne doit pas essayer de recreer Eloquent en v1. Son role est de proposer une connexion PDO propre, un helper de requetes et un model de base.

La database doit s'integrer au kernel par configuration et provider, mais rester optionnelle. Une application Velt doit pouvoir rendre une page sans installer ou configurer une base de donnees.

## Perimetre

Inclus :

- lecture de configuration database ;
- connexion PDO ;
- execution de requetes preparees ;
- transactions simples ;
- model de base optionnel ;
- provider database minimal ;
- tests SQLite en memoire.

Exclus :

- ORM complet ;
- relations avancees ;
- migrations avancees ;
- support MongoDB dans le Module 1.

## Comment tester sans vraie base externe

Le Module 1 doit utiliser SQLite en memoire pour tous les tests automatises.

- `ConnectionFactory` cree une connexion `sqlite::memory:`.
- Les requetes preparees sont testees avec une table temporaire.
- Les transactions sont testees avec commit et rollback.
- `DatabaseManager` lit une configuration fake depuis un `ConfigRepositoryInterface` fake.
- Le provider database enregistre une connexion dans un container fake ou le container kernel reel si disponible.

Il ne faut pas demander MySQL ou PostgreSQL pour valider le Module 1. Ces drivers peuvent etre documentes, mais SQLite suffit pour prouver les contrats.

## Issues

- [Issue 01 - Creer Database Manager PDO](issues/01-creer-database-manager-pdo.md)
- [Issue 02 - Ajouter Query Helper securise](issues/02-ajouter-query-helper-securise.md)
- [Issue 03 - Creer BaseModel MVP](issues/03-creer-basemodel-mvp.md)
- [Issue 04 - Integrer database avec configuration kernel](issues/04-integrer-database-configuration-kernel.md)
- [Issue 05 - Ajouter DatabaseServiceProvider minimal](issues/05-database-service-provider-minimal.md)
