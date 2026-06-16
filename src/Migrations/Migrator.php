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

        // Les fichiers sont tries par nom pour respecter l'ordre chronologique des timestamps.
        foreach ($this->files() as $name => $file) {
            if (in_array($name, $ran, true)) {
                continue;
            }

            $migration = $this->load($file);
            $migration->up();
            // On log seulement apres un up() reussi pour eviter un etat partiellement marque comme migre.
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
            // Le rollback supprime l'entree apres down(), pour pouvoir retenter en cas d'erreur.
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

        // Le format attendu est volontairement simple: un objet anonyme avec up() et down().
        if (!is_object($migration) || !method_exists($migration, 'up') || !method_exists($migration, 'down')) {
            throw new RuntimeException(sprintf('Migration "%s" must return an object with up() and down() methods.', $file));
        }

        return $migration;
    }
}
