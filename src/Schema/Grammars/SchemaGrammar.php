<?php

declare(strict_types=1);

namespace Velt\Database\Schema\Grammars;

use Velt\Database\Query\SqlIdentifier;
use Velt\Database\Schema\Blueprint;

abstract class SchemaGrammar
{
    public function compileCreate(Blueprint $blueprint): string
    {
        // Transforme les colonnes abstraites du Blueprint en colonnes SQL concretes.
        $columns = array_map(
            fn (array $column): string => $this->compileColumn($column),
            $blueprint->columns(),
        );

        return sprintf(
            'CREATE TABLE %s (%s)',
            SqlIdentifier::quote($blueprint->table()),
            implode(', ', $columns),
        );
    }

    public function compileDrop(string $table): string
    {
        return sprintf('DROP TABLE IF EXISTS %s', SqlIdentifier::quote($table));
    }

    /**
     * @param array{name:string,type:string,nullable:bool} $column
     */
    protected function compileColumn(array $column): string
    {
        $sql = SqlIdentifier::quote($column['name']) . ' ' . $this->type($column['type']);

        // Les colonnes id embarquent deja leurs contraintes propres dans chaque driver.
        if (!$column['nullable'] && $column['type'] !== 'id') {
            $sql .= ' NOT NULL';
        }

        return $sql;
    }

    abstract protected function type(string $type): string;
}
