<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Velt\Database\ConnectionFactory;
use Velt\Database\Exceptions\DatabaseConfigurationException;
use Velt\Database\Exceptions\UnknownDatabaseDriverException;

final class ConnectionFactoryTest extends TestCase
{
    use RequiresSqlite;

    public function test_it_creates_sqlite_memory_connection(): void
    {
        $this->requireSqlite();

        $factory = new ConnectionFactory();

        $pdo = $factory->create([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        self::assertInstanceOf(PDO::class, $pdo);

        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
        $pdo->prepare('INSERT INTO users (name) VALUES (?)')->execute(['Ada']);

        $statement = $pdo->prepare('SELECT name FROM users WHERE id = ?');
        $statement->execute([1]);

        self::assertSame('Ada', $statement->fetchColumn());
    }

    public function test_it_builds_mysql_dsn(): void
    {
        $factory = new ConnectionFactory();

        $dsn = $factory->buildDsn([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'velt',
            'charset' => 'utf8mb4',
        ]);

        self::assertSame('mysql:host=127.0.0.1;port=3306;dbname=velt;charset=utf8mb4', $dsn);
    }

    public function test_it_builds_pgsql_dsn(): void
    {
        $factory = new ConnectionFactory();

        $dsn = $factory->buildDsn([
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'velt',
        ]);

        self::assertSame('pgsql:host=127.0.0.1;port=5432;dbname=velt', $dsn);
    }

    public function test_it_throws_for_unknown_driver(): void
    {
        $factory = new ConnectionFactory();

        $this->expectException(UnknownDatabaseDriverException::class);
        $this->expectExceptionMessage('Unsupported database driver "sqlserver".');

        $factory->buildDsn([
            'driver' => 'sqlserver',
            'database' => 'velt',
        ]);
    }

    public function test_it_throws_explicit_error_for_missing_driver(): void
    {
        $factory = new ConnectionFactory();

        $this->expectException(DatabaseConfigurationException::class);
        $this->expectExceptionMessage('Missing required database key "driver".');

        $factory->buildDsn([
            'database' => ':memory:',
        ]);
    }
}
