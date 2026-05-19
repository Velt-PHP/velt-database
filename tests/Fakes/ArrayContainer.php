<?php

declare(strict_types=1);

namespace Velt\Database\Tests\Fakes;

use RuntimeException;
use Velt\Database\Contracts\ContainerInterface;

final class ArrayContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, callable> */
    private array $singletons = [];

    public function set(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->singletons[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (array_key_exists($id, $this->singletons)) {
            $instance = $this->singletons[$id]($this);
            $this->instances[$id] = $instance;

            return $instance;
        }

        throw new RuntimeException(sprintf('Service "%s" is not registered.', $id));
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || array_key_exists($id, $this->singletons);
    }
}
