<?php

declare(strict_types=1);

namespace Velt\Database\Factories;

use Velt\Database\DB;

abstract class Factory
{
    private int $count = 1;

    /**
     * @return array<string, mixed>
     */
    abstract public function definition(): array;

    abstract protected function table(): string;

    public static function new(): static
    {
        // Point d'entree lisible pour les tests: UserFactory::new()->make().
        return new static();
    }

    public function count(int $count): static
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('Factory count must be greater than zero.');
        }

        $this->count = $count;

        return $this;
    }

    /**
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function make(): array
    {
        if ($this->count === 1) {
            return $this->definition();
        }

        $items = [];

        // Chaque iteration rappelle definition() pour permettre des valeurs dynamiques.
        for ($i = 0; $i < $this->count; $i++) {
            $items[] = $this->definition();
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    public function create(): array
    {
        $items = $this->make();
        $rows = $this->count === 1 ? [$items] : $items;

        // create() persiste les donnees generees mais retourne le meme payload pour assertion/test.
        foreach ($rows as $row) {
            DB::table($this->table())->insert($row);
        }

        return $items;
    }
}
