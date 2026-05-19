<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Velt\Database\ConnectionFactory;
use Velt\Database\DatabaseManager;
use Velt\Database\Exceptions\DatabaseConfigurationException;
use Velt\Database\Tests\Fakes\ArrayConfigRepository;

final class DatabaseManagerTest extends TestCase
{
    public function test_it_resolves_default_connection_from_config(): void
    {
        $manager = new DatabaseManager(
            new ArrayConfigRepository([
                'database' => [
                    'default' => 'sqlite',
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                        ],
                    ],
                ],
            ]),
            new ConnectionFactory(),
        );

        $pdo = $manager->connection();

        self::assertInstanceOf(PDO::class, $pdo);
    }

    public function test_it_reuses_existing_connection_instance(): void
    {
        $manager = new DatabaseManager(
            new ArrayConfigRepository([
                'database' => [
                    'default' => 'sqlite',
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                        ],
                    ],
                ],
            ]),
        );

        $first = $manager->connection();
        $second = $manager->connection();

        self::assertSame($first, $second);
    }

    public function test_it_throws_when_default_connection_is_missing(): void
    {
        $manager = new DatabaseManager(
            new ArrayConfigRepository([
                'database' => [
                    'connections' => [],
                ],
            ]),
        );

        $this->expectException(DatabaseConfigurationException::class);
        $this->expectExceptionMessage('Database default connection is not configured at "database.default".');

        $manager->connection();
    }

    public function test_it_throws_when_named_connection_config_is_missing(): void
    {
        $manager = new DatabaseManager(
            new ArrayConfigRepository([
                'database' => [
                    'default' => 'sqlite',
                    'connections' => [],
                ],
            ]),
        );

        $this->expectException(DatabaseConfigurationException::class);
        $this->expectExceptionMessage('Database connection "sqlite" is not configured.');

        $manager->connection();
    }
}
