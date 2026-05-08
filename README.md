# Sous-module 05 - Database PDO

## Mission

Ce sous-module fournit une couche database simple pour le MVP. Il ne doit pas essayer de recreer Eloquent en v1. Son role est de proposer une connexion PDO propre, un helper de requetes et un model de base.

## Perimetre

Inclus :

- lecture de configuration database ;
- connexion PDO ;
- execution de requetes preparees ;
- transactions simples ;
- model de base optionnel.

Exclus :

- ORM complet ;
- relations avancees ;
- migrations avancees ;
- support MongoDB dans le Module 1.

## Issues

- [Issue 01 - Creer Database Manager PDO](issues/01-creer-database-manager-pdo.md)
- [Issue 02 - Ajouter Query Helper securise](issues/02-ajouter-query-helper-securise.md)
- [Issue 03 - Creer BaseModel MVP](issues/03-creer-basemodel-mvp.md)
- [Issue 04 - Integrer database avec configuration kernel](issues/04-integrer-database-configuration-kernel.md)
