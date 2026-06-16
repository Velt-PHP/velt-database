<?php

declare(strict_types=1);

namespace Velt\Database\Cache;

final class NullDatabaseCache implements DatabaseCacheInterface
{
    public function get(string $key): mixed
    {
        return null;
    }

    public function put(string $key, mixed $value, int $seconds): void
    {
    }

    public function forget(string $key): void
    {
    }

    public function flush(): void
    {
    }

    public function remember(string $key, int $seconds, callable $callback): mixed
    {
        return $callback();
    }
}
