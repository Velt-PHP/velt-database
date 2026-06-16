<?php

declare(strict_types=1);

namespace Velt\Database;

use InvalidArgumentException;
use LogicException;

abstract class Model
{
    protected static string $table = '';

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int|string $id): ?array
    {
        return DB::table(static::tableName())->where('id', $id)->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return DB::table(static::tableName())->get();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function create(array $attributes): int
    {
        if ($attributes === []) {
            throw new InvalidArgumentException('Model::create() requires at least one attribute.');
        }

        return DB::table(static::tableName())->insert($attributes);
    }

    protected static function tableName(): string
    {
        if (static::$table === '') {
            throw new LogicException(sprintf('%s must define a non-empty static $table property.', static::class));
        }

        return static::$table;
    }
}
