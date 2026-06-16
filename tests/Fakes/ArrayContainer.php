<?php

declare(strict_types=1);

namespace Velt\Database\Tests\Fakes;

use RuntimeException;
use Velt\Kernel\Contracts\ContainerInterface;

final class ArrayContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, callable|string> */
    private array $singletons = [];

    public function set(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    public function bind(string $id, callable|string $resolver): void
    {
        $this->singletons[$id] = $resolver;
    }

    public function singleton(string $id, callable|string $resolver): void
    {
        $this->singletons[$id] = $resolver;
    }

    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function alias(string $abstract, string $alias): void
    {
        $this->instances[$alias] = $this->get($abstract);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (array_key_exists($id, $this->singletons)) {
            $resolver = $this->singletons[$id];
            $instance = is_callable($resolver) ? $resolver($this) : new $resolver();
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
