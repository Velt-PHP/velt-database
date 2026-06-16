# Issue - Ajouter les commandes CLI database

Labels: module:3-data-orm, area:database, area:cli, type:feature, priority:p0, status:ready

## Contexte

Le package `velt-database` fournit les APIs runtime pour migrations, schema builder, seeders et factories.

Le repo CLI ne doit pas etre modifie depuis `velt-database`. Cette issue est a transmettre au responsable de `veltphp-cli`.

## Commandes a ajouter dans `veltphp-cli`

### `php bin/velt make:migration <name>`

Objectif:

- Creer un fichier dans `database/migrations`.
- Nommer le fichier avec timestamp + nom normalise.
- Generer une migration qui retourne un objet avec `up()` et `down()`.

Template attendu:

```php
<?php

declare(strict_types=1);

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

### `php bin/velt migrate`

Objectif:

- Charger l'application projet.
- Resoudre `DatabaseManager`.
- Executer `Velt\Database\Migrations\Migrator`.
- Afficher les migrations executees.

API database:

```php
$migrator = new Velt\Database\Migrations\Migrator($projectPath . '/database/migrations');
$executed = $migrator->migrate();
```

### `php bin/velt migrate:rollback`

Objectif:

- Rollback la derniere batch de migrations.
- Afficher les migrations annulees.

API database:

```php
$migrator = new Velt\Database\Migrations\Migrator($projectPath . '/database/migrations');
$rolledBack = $migrator->rollback();
```

### `php bin/velt make:seeder <name>`

Objectif:

- Creer un fichier dans `database/seeders`.
- Generer une classe qui etend `Velt\Database\Seeders\Seeder`.

Template attendu:

```php
<?php

declare(strict_types=1);

use Velt\Database\DB;
use Velt\Database\Seeders\Seeder;

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

### `php bin/velt db:seed [--class=DatabaseSeeder]`

Objectif:

- Charger le seeder demande.
- Executer `Velt\Database\Seeders\SeederRunner`.

API database:

```php
(new Velt\Database\Seeders\SeederRunner())->run(DatabaseSeeder::class);
```

## Criteres d'acceptation CLI

- `make:migration` n'ecrase pas un fichier existant sans `--force`.
- `migrate` cree la table `migrations` si elle n'existe pas.
- `migrate:rollback` annule la derniere batch.
- `make:seeder` genere un seeder executable.
- `db:seed` peut executer un seeder.
- Les commandes acceptent `--path=/project`, comme les commandes CLI existantes.
- Tests CLI couvrant generation de fichiers et execution dry-run si necessaire.
