<?php

declare(strict_types=1);

namespace Velt\Database\Contracts;

interface ConfigRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;
}
