<?php

declare(strict_types=1);

namespace Velt\Database\Tests\Fakes;

use Velt\Database\Contracts\ConfigRepositoryInterface;

final class ArrayConfigRepository implements ConfigRepositoryInterface
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cursor = $this->config;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return $default;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }
}
