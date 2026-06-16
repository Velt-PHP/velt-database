<?php

declare(strict_types=1);

namespace Velt\Database\Seeders;

abstract class Seeder
{
    abstract public function run(): void;

    /**
     * @param class-string<Seeder>|Seeder $seeder
     */
    protected function call(string|Seeder $seeder): void
    {
        $instance = is_string($seeder) ? new $seeder() : $seeder;
        $instance->run();
    }
}
