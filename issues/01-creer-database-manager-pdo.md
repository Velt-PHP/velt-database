# Issue 01 - Creer Database Manager PDO

## Labels

`module:1-foundations`, `area:database`, `type:feature`, `priority:p1`, `status:ready`

## Objectif

Creer le gestionnaire de connexion PDO pour Velt.

## Configuration cible

```php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => 'velt',
            'username' => 'root',
            'password' => '',
        ],
    ],
];
```

## Travail attendu

- Creer `DatabaseManager`.
- Creer `ConnectionFactory`.
- Supporter MySQL, PostgreSQL et SQLite pour le MVP.
- Configurer PDO en mode exception.
- Fournir `connection(?string $name = null): PDO`.

## Contraintes

- Pas de NoSQL dans le Module 1.
- Pas d'ORM complet.
- Les credentials ne doivent pas etre hardcodes.

## Criteres d'acceptation

- Une connexion SQLite en memoire peut etre creee en test.
- Les erreurs de configuration sont explicites.
- Le driver inconnu lance une exception dediee.

## Definition of Done

- Manager implemente.
- Tests avec SQLite.
- Documentation de configuration.

