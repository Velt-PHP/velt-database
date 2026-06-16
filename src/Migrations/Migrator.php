<?php

declare(strict_types=1);

namespace Velt\Database\Migrations;

use RuntimeException;

final class Migrator
{
    public function __construct(
        private readonly string $path,
        private readonly MigrationRepository $repository = new MigrationRepository(),
    ) {
    }

    /**
     * @return list<string>
     */
    public function migrate(): array
    {
        $ran = $this->repository->ran();
        $batch = $this->repository->nextBatch();
        $executed = [];

        foreach ($this->files() as $name => $file) {
            if (in_array($name, $ran, true)) {
                continue;
            }

            $migration = $this->load($file);
            $migration->up();
            $this->repository->log($name, $batch);
            $executed[] = $name;
        }

        return $executed;
    }

    /**
     * @return list<string>
     */
    public function rollback(): array
    {
        $rolledBack = [];

        foreach ($this->repository->lastBatch() as $name) {
            $file = $this->path . DIRECTORY_SEPARATOR . $name;

            if (!is_file($file)) {
                throw new RuntimeException(sprintf('Migration file "%s" was not found.', $file));
            }

            $migration = $this->load($file);
            $migration->down();
            $this->repository->delete($name);
            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    /**
     * @return array<string, string>
     */
    private function files(): array
    {
        $files = [];

        foreach (glob($this->path . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $files[basename($file)] = $file;
        }

        ksort($files);

        return $files;
    }

    private function load(string $file): object
    {
        $migration = require $file;

        if (!is_object($migration) || !method_exists($migration, 'up') || !method_exists($migration, 'down')) {
            throw new RuntimeException(sprintf('Migration "%s" must return an object with up() and down() methods.', $file));
        }

        return $migration;
    }
}
