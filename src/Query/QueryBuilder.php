<?php

declare(strict_types=1);

namespace Velt\Database\Query;

use InvalidArgumentException;
use Velt\Database\DB;

final class QueryBuilder
{
    /** @var list<string> */
    private array $columns = ['*'];

    /** @var list<array{column:string,operator:string,value:mixed}> */
    private array $wheres = [];

    /** @var list<array{column:string,direction:string}> */
    private array $orders = [];

    private ?int $limit = null;

    private ?int $rememberTtl = null;

    public function __construct(
        private readonly string $table,
        private readonly ?string $connection = null,
    ) {
        // Valide le nom de table immediatement: les identifiers ne peuvent pas etre bindes comme les valeurs.
        SqlIdentifier::quote($table);
    }

    public function select(string ...$columns): self
    {
        $this->columns = $columns === [] ? ['*'] : array_values($columns);

        return $this;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        $operator = '=';

        // Supporte where('email', $email) et where('age', '>=', 18).
        if (func_num_args() >= 3) {
            $operator = strtolower((string) $operatorOrValue);
        } else {
            $value = $operatorOrValue;
        }

        if (!in_array($operator, ['=', '!=', '<>', '>', '>=', '<', '<=', 'like'], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported where operator "%s".', $operator));
        }

        SqlIdentifier::quote($column);

        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $direction = strtolower($direction);

        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Order direction must be "asc" or "desc".');
        }

        SqlIdentifier::quote($column);

        $this->orders[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be greater than zero.');
        }

        $this->limit = $limit;

        return $this;
    }

    public function remember(int $seconds): self
    {
        if ($seconds < 1) {
            throw new InvalidArgumentException('Cache TTL must be greater than zero.');
        }

        // Le TTL est stocke sur le builder et applique uniquement aux lectures get()/first().
        $this->rememberTtl = $seconds;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        [$sql, $bindings] = $this->toSelectSql();

        if ($this->rememberTtl === null) {
            return DB::select($sql, $bindings, $this->connection);
        }

        // La cle inclut SQL + bindings pour distinguer deux requetes avec la meme structure.
        $key = $this->cacheKey($sql, $bindings);

        return DB::cache()->remember(
            $key,
            $this->rememberTtl,
            fn (): array => DB::select($sql, $bindings, $this->connection),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();

        return $rows[0] ?? null;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function insert(array $values): int
    {
        if ($values === []) {
            throw new InvalidArgumentException('Insert values cannot be empty.');
        }

        $columns = array_keys($values);
        $columnSql = implode(', ', array_map([SqlIdentifier::class, 'quote'], $columns));
        // Les valeurs restent toujours des placeholders prepares.
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        return DB::statement(
            sprintf('INSERT INTO %s (%s) VALUES (%s)', SqlIdentifier::quote($this->table), $columnSql, $placeholders),
            array_values($values),
            $this->connection,
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    public function update(array $values): int
    {
        if ($values === []) {
            throw new InvalidArgumentException('Update values cannot be empty.');
        }

        $setSql = implode(', ', array_map(
            static fn (string $column): string => SqlIdentifier::quote($column) . ' = ?',
            array_keys($values),
        ));

        [$whereSql, $whereBindings] = $this->compileWhere();

        return DB::statement(
            sprintf('UPDATE %s SET %s%s', SqlIdentifier::quote($this->table), $setSql, $whereSql),
            array_merge(array_values($values), $whereBindings),
            $this->connection,
        );
    }

    public function delete(): int
    {
        [$whereSql, $bindings] = $this->compileWhere();

        return DB::statement(
            sprintf('DELETE FROM %s%s', SqlIdentifier::quote($this->table), $whereSql),
            $bindings,
            $this->connection,
        );
    }

    /**
     * @return array{0:string,1:list<mixed>}
     */
    public function toSelectSql(): array
    {
        $columns = implode(', ', array_map([SqlIdentifier::class, 'quote'], $this->columns));
        [$whereSql, $bindings] = $this->compileWhere();
        $orderSql = $this->compileOrders();
        $limitSql = $this->limit === null ? '' : ' LIMIT ' . $this->limit;

        return [
            sprintf('SELECT %s FROM %s%s%s%s', $columns, SqlIdentifier::quote($this->table), $whereSql, $orderSql, $limitSql),
            $bindings,
        ];
    }

    /**
     * @return array{0:string,1:list<mixed>}
     */
    private function compileWhere(): array
    {
        if ($this->wheres === []) {
            return ['', []];
        }

        $bindings = [];
        $parts = [];

        foreach ($this->wheres as $where) {
            // Seules les valeurs partent en bindings; les colonnes sont validees puis quotees.
            $parts[] = sprintf('%s %s ?', SqlIdentifier::quote($where['column']), strtoupper($where['operator']));
            $bindings[] = $where['value'];
        }

        return [' WHERE ' . implode(' AND ', $parts), $bindings];
    }

    private function compileOrders(): string
    {
        if ($this->orders === []) {
            return '';
        }

        $parts = array_map(
            static fn (array $order): string => SqlIdentifier::quote($order['column']) . ' ' . strtoupper($order['direction']),
            $this->orders,
        );

        return ' ORDER BY ' . implode(', ', $parts);
    }

    /**
     * @param list<mixed> $bindings
     */
    private function cacheKey(string $sql, array $bindings): string
    {
        return 'query:' . hash('sha256', serialize([$this->connection, $sql, $bindings]));
    }
}
