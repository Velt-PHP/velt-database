<?php

declare(strict_types=1);

namespace Velt\Database\Schema;

use InvalidArgumentException;
use Velt\Database\DB;
use Velt\Database\Schema\Grammars\MySqlSchemaGrammar;
use Velt\Database\Schema\Grammars\PostgresSchemaGrammar;
use Velt\Database\Schema\Grammars\SchemaGrammar;
use Velt\Database\Schema\Grammars\SQLiteSchemaGrammar;

final class SchemaBuilder
{
    public function __construct(private readonly ?string $connection = null)
    {
    }

    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        DB::statement($this->grammar()->compileCreate($blueprint), [], $this->connection);
    }

    public function drop(string $table): void
    {
        DB::statement($this->grammar()->compileDrop($table), [], $this->connection);
    }

    public function table(string $table, callable $callback): void
    {
        throw new InvalidArgumentException('Schema::table() is reserved for future ALTER TABLE support.');
    }

    private function grammar(): SchemaGrammar
    {
        $driver = DB::connection($this->connection)->getAttribute(\PDO::ATTR_DRIVER_NAME);

        return match ($driver) {
            'sqlite' => new SQLiteSchemaGrammar(),
            'mysql' => new MySqlSchemaGrammar(),
            'pgsql' => new PostgresSchemaGrammar(),
            default => throw new InvalidArgumentException(sprintf('Unsupported schema driver "%s".', $driver)),
        };
    }
}
