<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Database\DatabaseManager;
use Velt\Database\DatabaseServiceProvider;
use Velt\Database\Exceptions\DatabaseConfigurationException;
use Velt\Database\Tests\Fakes\ArrayConfigRepository;
use Velt\Database\Tests\Fakes\ArrayContainer;
use Velt\Kernel\Contracts\ApplicationInterface;
use Velt\Kernel\Contracts\ConfigRepositoryInterface;
use Velt\Kernel\Contracts\ContainerInterface;
use Velt\Kernel\Contracts\EnvRepositoryInterface;
use Velt\Kernel\Contracts\EventDispatcherInterface;
use Velt\Kernel\Contracts\ExceptionHandlerInterface;
use Velt\Kernel\Contracts\ServiceProviderInterface;

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

        $provider = new DatabaseServiceProvider($this->appFor($container));
        $provider->register();

        self::assertTrue($container->has(DatabaseManager::class));
        $manager = $container->get(DatabaseManager::class);
        self::assertInstanceOf(DatabaseManager::class, $manager);
    }

    public function test_it_uses_lazy_resolution_and_fails_only_when_database_is_used(): void
    {
        $container = new ArrayContainer();
        $container->set(ConfigRepositoryInterface::class, new ArrayConfigRepository([]));

        $provider = new DatabaseServiceProvider($this->appFor($container));
        $provider->register();

        $manager = $container->get(DatabaseManager::class);
        self::assertInstanceOf(DatabaseManager::class, $manager);

        $this->expectException(DatabaseConfigurationException::class);
        $this->expectExceptionMessage('Database default connection is not configured at "database.default".');

        $manager->connection();
    }

    private function appFor(ArrayContainer $container): ApplicationInterface
    {
        return new class ($container) implements ApplicationInterface {
            public function __construct(private readonly ArrayContainer $container)
            {
            }

            public function basePath(): string
            {
                return __DIR__;
            }

            public function container(): ContainerInterface
            {
                return $this->container;
            }

            public function config(): ConfigRepositoryInterface
            {
                return $this->container->get(ConfigRepositoryInterface::class);
            }

            public function events(): EventDispatcherInterface
            {
                throw new \LogicException('Not used by this test.');
            }

            public function env(): EnvRepositoryInterface
            {
                throw new \LogicException('Not used by this test.');
            }

            public function exceptions(): ExceptionHandlerInterface
            {
                throw new \LogicException('Not used by this test.');
            }

            public function environment(): string
            {
                return 'testing';
            }

            public function isLocal(): bool
            {
                return false;
            }

            public function isTesting(): bool
            {
                return true;
            }

            public function isProduction(): bool
            {
                return false;
            }

            public function isDebug(): bool
            {
                return false;
            }

            public function registerProvider(string|ServiceProviderInterface $provider): ServiceProviderInterface
            {
                throw new \LogicException('Not used by this test.');
            }

            public function boot(): void
            {
            }
        };
    }
}
