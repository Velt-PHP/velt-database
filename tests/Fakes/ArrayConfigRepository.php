<?php

declare(strict_types=1);

namespace Velt\Database\Tests\Fakes;

use Velt\Kernel\Contracts\ConfigRepositoryInterface;

final class ArrayConfigRepository implements ConfigRepositoryInterface
{
    /** @var array<string, mixed> */
    private array $config;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $this->config = $config;
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

    public function set(string $key, mixed $value): void
    {
        $cursor = &$this->config;

        foreach (explode('.', $key) as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }

        $cursor = $value;
    }

    public function has(string $key): bool
    {
        $cursor = $this->config;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return false;
            }

            $cursor = $cursor[$segment];
        }

        return true;
    }

    public function all(): array
    {
        return $this->config;
    }
}
