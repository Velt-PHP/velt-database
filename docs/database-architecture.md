# Architecture du composant Velt Database

Ce document explique l'architecture actuelle de `velt-database`, les fichiers importants, leurs roles, et les choix faits pour les nouvelles features database.

## Objectif du composant

`velt-database` fournit la couche data du framework Velt.

Il couvre maintenant :

- connexions PDO via `DatabaseManager` et `ConnectionFactory` ;
- facade statique `DB` ;
- query builder fluide ;
- base model minimal ;
- schema builder ;
- migrations runtime ;
- seeders ;
- factories ;
- cache de resultats ;
- integration avec les contracts du kernel.

Les commandes CLI ne sont pas implementees dans ce repo. Elles doivent etre ajoutees dans `veltphp-cli`. Une issue dediee existe ici :

```text
issues/06-cli-database-commands.md
```

## Architecture globale

```text
velt-database/
├── composer.json
├── phpunit.xml
├── README.md
├── docs/
│   └── database-architecture.md
├── issues/
│   └── 06-cli-database-commands.md
├── src/
│   ├── ConnectionFactory.php
│   ├── DatabaseManager.php
│   ├── DatabaseServiceProvider.php
│   ├── DB.php
│   ├── Model.php
│   ├── Cache/
│   ├── Exceptions/
│   ├── Factories/
│   ├── Migrations/
│   ├── Query/
│   ├── Schema/
│   └── Seeders/
└── tests/
    ├── Fakes/
    ├── RequiresSqlite.php
    ├── QueryBuilderTest.php
    ├── SchemaMigrationTest.php
    └── SeederFactoryCacheTest.php
```

## Flux principal

```text
Velt Kernel Application
        │
        ▼
DatabaseServiceProvider
        │
        ├── recupere ConfigRepositoryInterface depuis le container kernel
        ├── cree DatabaseManager
        ├── configure DB::setManager()
        └── configure le cache database
                │
                ▼
              DB facade
                │
                ├── select / first / statement / transaction
                ├── table() -> QueryBuilder
                └── cache() -> DatabaseCacheInterface
                        │
                        ▼
                  PDO via DatabaseManager
```

## Integration Kernel

`velt-database` ne definit plus ses propres contracts de container/config.

Les contracts viennent du kernel :

```php
use Velt\Kernel\Contracts\ConfigRepositoryInterface;
use Velt\Kernel\Contracts\ContainerInterface;
use Velt\Kernel\ServiceProvider;
```

Source :

```text
veltphp-kernel/packages/kernel/src/Contracts/
```

Importance :

- evite deux sources de verite ;
- permet a `DatabaseServiceProvider` d'etre enregistre avec `Application::registerProvider()` ;
- garde database compatible avec le container officiel du framework.

## Fichiers racine

### `composer.json`

Declare le package :

```json
"name": "velt/database"
```

Declare aussi la dependance kernel :

```json
"velt/kernel": "dev-main"
```

En local, les repositories `path` pointent vers :

```text
../veltphp-kernel/packages/kernel
../velt-ui
```

Importance :

- Composer peut autoload les contracts kernel ;
- les tests database peuvent utiliser le kernel sans bootstrap manuel ;
- le skeleton peut consommer `velt/database` comme package.

### `phpunit.xml`

Configure PHPUnit avec :

```xml
<phpunit bootstrap="vendor/autoload.php" colors="true">
```

Importance :

- charge Composer autoload ;
- lance tous les tests du dossier `tests/`.

## Core database

### `src/ConnectionFactory.php`

Role :

- lire la config d'une connexion ;
- construire le DSN PDO ;
- creer l'instance `PDO`.

Drivers geres :

- `sqlite`
- `mysql`
- `pgsql`

Importance :

- isole la logique de connexion ;
- centralise les options PDO ;
- garde `DatabaseManager` simple.

### `src/DatabaseManager.php`

Role :

- resoudre la connexion par defaut ;
- creer les connexions via `ConnectionFactory` ;
- garder les connexions en cache pendant l'execution.

Importance :

- evite de recreer plusieurs fois le meme PDO ;
- supporte plusieurs connexions nommees ;
- lit la configuration via `ConfigRepositoryInterface` du kernel.

### `src/DatabaseServiceProvider.php`

Role :

- s'enregistrer dans l'application kernel ;
- enregistrer `DatabaseManager` comme singleton ;
- configurer la facade `DB` ;
- configurer le cache database.

Importance :

- point d'integration officiel avec Velt Kernel ;
- rend database utilisable via `Application::registerProvider(DatabaseServiceProvider::class)`.

### `src/DB.php`

Role :

- facade statique database ;
- expose les helpers SQL bas niveau ;
- expose le query builder ;
- expose le cache.

API principale :

```php
DB::select($sql, $bindings);
DB::first($sql, $bindings);
DB::statement($sql, $bindings);
DB::transaction(fn () => ...);
DB::table('users');
DB::cache();
```

Importance :

- point d'entree simple pour les apps ;
- garde les requetes preparees au centre ;
- permet aux models, migrations, seeders et factories de partager la meme connexion.

### `src/Model.php`

Role :

- base model minimal ;
- fournit `find`, `all`, `create`.

Depuis la mise a jour, il utilise le query builder :

```php
DB::table(static::tableName())->where('id', $id)->first();
```

Importance :

- evite le SQL brut dans les models ;
- pose la base pour un ORM plus complet plus tard.

## Query Builder

```text
src/Query/
├── QueryBuilder.php
└── SqlIdentifier.php
```

### `QueryBuilder.php`

Role :

- construire des requetes SQL courantes ;
- binder toutes les valeurs dynamiques ;
- executer `get`, `first`, `insert`, `update`, `delete`.

API :

```php
DB::table('users')->where('email', $email)->first();
DB::table('users')->select('id', 'name')->orderBy('id')->limit(10)->get();
DB::table('users')->insert(['name' => 'Ada']);
DB::table('users')->where('id', 1)->update(['name' => 'Ada']);
DB::table('users')->where('id', 1)->delete();
```

Importance :

- remplace le SQL brut dans les cas courants ;
- toutes les valeurs passent par prepared statements ;
- sert de base pour models, seeders, factories et cache.

### `SqlIdentifier.php`

Role :

- valider les noms de tables et colonnes ;
- quoter les identifiers SQL.

Importance :

- les valeurs peuvent etre bindees, mais pas les noms de colonnes ;
- ce fichier reduit le risque d'injection via table/column name.

## Schema Builder

```text
src/Schema/
├── Blueprint.php
├── Schema.php
├── SchemaBuilder.php
└── Grammars/
    ├── SchemaGrammar.php
    ├── SQLiteSchemaGrammar.php
    ├── MySqlSchemaGrammar.php
    └── PostgresSchemaGrammar.php
```

### `Schema.php`

Role :

- facade statique pour le schema builder.

API :

```php
Schema::create('users', function (Blueprint $table): void {
    $table->id();
    $table->string('name');
    $table->integer('age');
    $table->timestamps();
});

Schema::drop('users');
```

### `Blueprint.php`

Role :

- decrire la table a creer ;
- stocker les colonnes demandees.

Colonnes supportees maintenant :

- `id()`
- `string()`
- `integer()`
- `timestamps()`

### `SchemaBuilder.php`

Role :

- transformer un `Blueprint` en SQL ;
- choisir la grammar selon le driver PDO.

### `Grammars/*`

Role :

- generer le SQL adapte a chaque driver.

Importance :

- SQLite, MySQL et PostgreSQL n'ont pas les memes types SQL ;
- separer les grammars evite de melanger les specificites driver dans le reste du code.

## Migrations

```text
src/Migrations/
├── Migrator.php
└── MigrationRepository.php
```

### `MigrationRepository.php`

Role :

- creer la table `migrations` ;
- lire les migrations deja executees ;
- enregistrer une migration executee ;
- supprimer une migration rollbackee ;
- calculer les batches.

Importance :

- permet de savoir ce qui a deja ete lance ;
- rend possible le rollback de la derniere batch.

### `Migrator.php`

Role :

- lire les fichiers de migrations ;
- executer `up()` ;
- executer `down()` lors du rollback ;
- synchroniser avec `MigrationRepository`.

Format attendu d'une migration :

```php
<?php

use Velt\Database\Schema\Blueprint;
use Velt\Database\Schema\Schema;

return new class {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('users');
    }
};
```

## Seeders

```text
src/Seeders/
├── Seeder.php
└── SeederRunner.php
```

### `Seeder.php`

Role :

- classe de base pour les seeders ;
- impose une methode `run()`.

### `SeederRunner.php`

Role :

- executer un seeder donne ;
- accepter une instance ou un nom de classe.

Exemple :

```php
final class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Ada',
            'email' => 'ada@example.com',
        ]);
    }
}
```

## Factories

```text
src/Factories/
└── Factory.php
```

Role :

- generer un tableau de donnees ;
- inserer les donnees en base avec `create()`.

Exemple :

```php
final class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Ada',
            'email' => 'ada@example.com',
        ];
    }

    protected function table(): string
    {
        return 'users';
    }
}
```

Utilisation :

```php
UserFactory::new()->make();
UserFactory::new()->create();
UserFactory::new()->count(3)->create();
```

## Cache database

```text
src/Cache/
├── DatabaseCacheInterface.php
├── FileDatabaseCache.php
└── NullDatabaseCache.php
```

### `DatabaseCacheInterface.php`

Role :

- definir le contrat de cache database.

### `FileDatabaseCache.php`

Role :

- stocker les resultats dans des fichiers ;
- appliquer un TTL ;
- permettre `forget()` et `flush()`.

### `NullDatabaseCache.php`

Role :

- implementation qui ne cache rien ;
- utilisee par defaut et en environnement `testing`.

API :

```php
DB::table('users')->where('active', 1)->remember(60)->get();
DB::cache()->flush();
```

Importance :

- permet le cache sans imposer Redis ;
- garde les tests predictibles.

## Exceptions

```text
src/Exceptions/
├── DatabaseConfigurationException.php
└── UnknownDatabaseDriverException.php
```

Role :

- fournir des erreurs explicites sur la configuration ;
- signaler les drivers non supportes.

## Tests

Tests ajoutes ou modifies :

```text
tests/
├── RequiresSqlite.php
├── QueryBuilderTest.php
├── SchemaMigrationTest.php
├── SeederFactoryCacheTest.php
├── DatabaseKernelIntegrationTest.php
└── ...
```

### `RequiresSqlite.php`

Role :

- ignorer proprement les tests qui demandent `pdo_sqlite` si l'extension n'est pas installee.

Importance :

- evite les erreurs `PDOException: could not find driver` ;
- garde la suite verte sur les machines qui n'ont pas SQLite active.

## Personnalisation des commandes de migration

Oui, les commandes de migration peuvent etre personnalisees, mais dans le repo CLI, pas dans `velt-database`.

Le package `velt-database` fournit le moteur :

```php
$migrator = new Velt\Database\Migrations\Migrator($path);
$migrator->migrate();
$migrator->rollback();
```

Le repo `veltphp-cli` peut ensuite choisir :

- le nom des commandes ;
- les options ;
- le dossier de migrations ;
- le format des messages ;
- les templates generes ;
- les flags comme `--path`, `--force`, `--pretend`, `--step`, `--database`.

Exemples de personnalisations possibles :

```text
php bin/velt migrate
php bin/velt migrate --path=database/migrations
php bin/velt migrate --database=testing
php bin/velt migrate:rollback
php bin/velt migrate:rollback --step=1
php bin/velt make:migration create_users_table
php bin/velt make:migration create_users_table --table=users
php bin/velt make:migration create_users_table --create=users
```

La logique recommandee :

- `velt-database` reste responsable du moteur ;
- `veltphp-cli` reste responsable de l'experience terminal ;
- le skeleton peut definir des conventions par defaut comme `database/migrations`.

## Etat actuel

Ce qui est deja fait :

- Query Builder runtime ;
- Schema Builder runtime ;
- Migrator runtime ;
- Seeders runtime ;
- Factories runtime ;
- Cache fichier runtime ;
- issue CLI separee ;
- tests ajoutes ;
- tests SQLite skippables si extension absente.

Ce qui reste cote CLI :

- `make:migration` ;
- `migrate` ;
- `migrate:rollback` ;
- `make:seeder` ;
- `db:seed`.

Ce travail est documente dans :

```text
issues/06-cli-database-commands.md
```
