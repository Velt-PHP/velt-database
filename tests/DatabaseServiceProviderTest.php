<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Database\Contracts\ConfigRepositoryInterface;
use Velt\Database\DatabaseManager;
use Velt\Database\DatabaseServiceProvider;
use Velt\Database\Exceptions\DatabaseConfigurationException;
use Velt\Database\Tests\Fakes\ArrayConfigRepository;
use Velt\Database\Tests\Fakes\ArrayContainer;

final class DatabaseServiceProviderTest extends TestCase
{
    public function test_it_registers_database_manager_in_container(): void
    {
        $container = new ArrayContainer();
        $container->set(ConfigRepositoryInterface::class, new ArrayConfigRepository([
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
        ]));

        $provider = new DatabaseServiceProvider();
        $provider->register($container);

        self::assertTrue($container->has(DatabaseManager::class));
        $manager = $container->get(DatabaseManager::class);
        self::assertInstanceOf(DatabaseManager::class, $manager);
        self::assertInstanceOf(\PDO::class, $manager->connection());
    }

    public function test_it_uses_lazy_resolution_and_fails_only_when_database_is_used(): void
    {
        $container = new ArrayContainer();
        $container->set(ConfigRepositoryInterface::class, new ArrayConfigRepository([]));

        $provider = new DatabaseServiceProvider();
        $provider->register($container);

        $manager = $container->get(DatabaseManager::class);
        self::assertInstanceOf(DatabaseManager::class, $manager);

        $this->expectException(DatabaseConfigurationException::class);
        $this->expectExceptionMessage('Database default connection is not configured at "database.default".');

        $manager->connection();
    }
}
