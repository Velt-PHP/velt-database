<?php

declare(strict_types=1);

namespace Velt\Database\Schema;

final class Blueprint
{
    /** @var list<array{name:string,type:string,nullable:bool}> */
    private array $columns = [];

    public function __construct(private readonly string $table)
    {
    }

    public function id(string $name = 'id'): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'id', 'nullable' => false];

        return $this;
    }

    public function string(string $name, bool $nullable = false): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'string', 'nullable' => $nullable];

        return $this;
    }

    public function integer(string $name, bool $nullable = false): self
    {
        $this->columns[] = ['name' => $name, 'type' => 'integer', 'nullable' => $nullable];

        return $this;
    }

    public function timestamps(): self
    {
        $this->columns[] = ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true];
        $this->columns[] = ['name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true];

        return $this;
    }

    public function table(): string
    {
        return $this->table;
    }

    /**
     * @return list<array{name:string,type:string,nullable:bool}>
     */
    public function columns(): array
    {
        return $this->columns;
    }
}
