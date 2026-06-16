<?php

declare(strict_types=1);

namespace Velt\Database\Schema\Grammars;

final class PostgresSchemaGrammar extends SchemaGrammar
{
    protected function type(string $type): string
    {
        return match ($type) {
            'id' => 'BIGSERIAL PRIMARY KEY',
            'string' => 'VARCHAR(255)',
            'integer' => 'INTEGER',
            'timestamp' => 'TIMESTAMP NULL',
            default => 'TEXT',
        };
    }
}
