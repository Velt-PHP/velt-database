<?php

declare(strict_types=1);

namespace Velt\Database\Cache;

final class FileDatabaseCache implements DatabaseCacheInterface
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    public function get(string $key): mixed
    {
        $path = $this->path($key);

        if (!is_file($path)) {
            return null;
        }

        $payload = unserialize((string) file_get_contents($path));

        if (!is_array($payload) || !isset($payload['expires_at'])) {
            $this->forget($key);

            return null;
        }

        if ((int) $payload['expires_at'] < time()) {
            $this->forget($key);

            return null;
        }

        return $payload['value'] ?? null;
    }

    public function put(string $key, mixed $value, int $seconds): void
    {
        file_put_contents($this->path($key), serialize([
            'expires_at' => time() + $seconds,
            'value' => $value,
        ]));
    }

    public function forget(string $key): void
    {
        $path = $this->path($key);

        if (is_file($path)) {
            unlink($path);
        }
    }

    public function flush(): void
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function remember(string $key, int $seconds, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $seconds);

        return $value;
    }

    private function path(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.cache';
    }
}
