<?php

declare(strict_types=1);

namespace Velt\Database\Contracts;

interface ContainerInterface
{
    public function singleton(string $id, callable $factory): void;

    public function get(string $id): mixed;

    public function has(string $id): bool;
}
