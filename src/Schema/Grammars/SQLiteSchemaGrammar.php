<?php

declare(strict_types=1);

namespace Velt\Database\Schema\Grammars;

final class SQLiteSchemaGrammar extends SchemaGrammar
{
    protected function type(string $type): string
    {
        return match ($type) {
            'id' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'string' => 'TEXT',
            'integer' => 'INTEGER',
            'timestamp' => 'TEXT',
            default => 'TEXT',
        };
    }
}
