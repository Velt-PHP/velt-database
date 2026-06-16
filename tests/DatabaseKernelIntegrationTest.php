<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Database\DatabaseManager;
use Velt\Database\DatabaseServiceProvider;
use Velt\Kernel\Application;

final class DatabaseKernelIntegrationTest extends TestCase
{
    public function test_database_provider_registers_manager_through_kernel_application(): void
    {
        $app = new Application(__DIR__, [
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
        ]);

        $app->registerProvider(DatabaseServiceProvider::class);

        self::assertTrue($app->container()->has(DatabaseManager::class));

        $manager = $app->container()->get(DatabaseManager::class);

        self::assertInstanceOf(DatabaseManager::class, $manager);
    }
}
