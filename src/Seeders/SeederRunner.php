<?php

declare(strict_types=1);

namespace Velt\Database\Seeders;

use InvalidArgumentException;

final class SeederRunner
{
    /**
     * @param class-string<Seeder>|Seeder $seeder
     */
    public function run(string|Seeder $seeder): void
    {
        // Accepte une instance deja construite ou un nom de classe genere par la CLI.
        $instance = is_string($seeder) ? new $seeder() : $seeder;

        if (!$instance instanceof Seeder) {
            throw new InvalidArgumentException('Seeder must extend ' . Seeder::class . '.');
        }

        $instance->run();
    }
}
