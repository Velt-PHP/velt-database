<?php

declare(strict_types=1);

namespace Velt\Database\Schema\Grammars;

final class MySqlSchemaGrammar extends SchemaGrammar
{
    protected function type(string $type): string
    {
        return match ($type) {
            'id' => 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
            'string' => 'VARCHAR(255)',
            'integer' => 'INT',
            'timestamp' => 'TIMESTAMP NULL',
            default => 'TEXT',
        };
    }
}
