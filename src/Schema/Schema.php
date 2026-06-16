<?php

declare(strict_types=1);

namespace Velt\Database\Schema;

final class Schema
{
    public static function create(string $table, callable $callback, ?string $connection = null): void
    {
        // Facade statique pour garder les migrations courtes et lisibles.
        (new SchemaBuilder($connection))->create($table, $callback);
    }

    public static function drop(string $table, ?string $connection = null): void
    {
        (new SchemaBuilder($connection))->drop($table);
    }

    public static function table(string $table, callable $callback, ?string $connection = null): void
    {
        (new SchemaBuilder($connection))->table($table, $callback);
    }
}
