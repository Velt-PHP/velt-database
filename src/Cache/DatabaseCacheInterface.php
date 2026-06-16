<?php

declare(strict_types=1);

namespace Velt\Database\Cache;

interface DatabaseCacheInterface
{
    /**
     * Retourne null en cas de cache miss ou d'entree expiree.
     */
    public function get(string $key): mixed;

    /**
     * Stocke une valeur pour un TTL exprime en secondes.
     */
    public function put(string $key, mixed $value, int $seconds): void;

    public function forget(string $key): void;

    public function flush(): void;

    /**
     * Execute le callback seulement en cas de cache miss.
     */
    public function remember(string $key, int $seconds, callable $callback): mixed;
}
