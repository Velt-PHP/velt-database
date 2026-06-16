<?php

declare(strict_types=1);

namespace Velt\Database\Migrations;

use Velt\Database\DB;

final class MigrationRepository
{
    public function ensureTable(): void
    {
        $driver = DB::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        // La table migrations garde un SQL par driver, car l'auto-increment differe selon PDO.
        $sql = match ($driver) {
            'sqlite' => 'CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, migration TEXT NOT NULL, batch INTEGER NOT NULL)',
            'mysql' => 'CREATE TABLE IF NOT EXISTS migrations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) NOT NULL, batch INT NOT NULL)',
            'pgsql' => 'CREATE TABLE IF NOT EXISTS migrations (id BIGSERIAL PRIMARY KEY, migration VARCHAR(255) NOT NULL, batch INTEGER NOT NULL)',
            default => 'CREATE TABLE IF NOT EXISTS migrations (id INTEGER PRIMARY KEY, migration VARCHAR(255) NOT NULL, batch INTEGER NOT NULL)',
        };

        DB::statement($sql);
    }

    /**
     * @return list<string>
     */
    public function ran(): array
    {
        $this->ensureTable();

        return array_map(
            static fn (array $row): string => (string) $row['migration'],
            DB::select('SELECT migration FROM migrations ORDER BY id ASC'),
        );
    }

    public function log(string $migration, int $batch): void
    {
        $this->ensureTable();
        DB::table('migrations')->insert(['migration' => $migration, 'batch' => $batch]);
    }

    public function delete(string $migration): void
    {
        DB::table('migrations')->where('migration', $migration)->delete();
    }

    public function nextBatch(): int
    {
        $this->ensureTable();
        $row = DB::first('SELECT MAX(batch) AS batch FROM migrations');

        // Une nouvelle execution prend toujours la batch suivante.
        return ((int) ($row['batch'] ?? 0)) + 1;
    }

    /**
     * @return list<string>
     */
    public function lastBatch(): array
    {
        $this->ensureTable();
        $row = DB::first('SELECT MAX(batch) AS batch FROM migrations');
        $batch = (int) ($row['batch'] ?? 0);

        // Aucune batch signifie qu'il n'y a rien a rollback.
        if ($batch === 0) {
            return [];
        }

        return array_map(
            static fn (array $row): string => (string) $row['migration'],
            DB::select('SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC', [$batch]),
        );
    }
}
