<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Database\ConnectionFactory;
use Velt\Database\DatabaseManager;
use Velt\Database\DB;
use Velt\Database\Model;
use Velt\Database\Tests\Fakes\ArrayConfigRepository;

final class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        DB::setManager($manager);

        DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL)');
        DB::statement('INSERT INTO users (name, email) VALUES (?, ?)', ['Ada', 'ada@example.com']);
    }

    protected function tearDown(): void
    {
        DB::clearManager();
        parent::tearDown();
    }

    public function test_find_uses_prepared_query_and_returns_row(): void
    {
        $user = UserModel::find(1);

        self::assertIsArray($user);
        self::assertSame('Ada', $user['name']);
    }

    public function test_all_returns_every_row(): void
    {
        UserModel::create(['name' => 'Linus', 'email' => 'linus@example.com']);

        $users = UserModel::all();

        self::assertCount(2, $users);
    }

    public function test_create_inserts_provided_fields(): void
    {
        $affected = UserModel::create(['name' => 'Grace', 'email' => 'grace@example.com']);
        $created = DB::first('SELECT email FROM users WHERE email = ?', ['grace@example.com']);

        self::assertSame(1, $affected);
        self::assertIsArray($created);
        self::assertSame('grace@example.com', $created['email']);
    }
}

final class UserModel extends Model
{
    protected static string $table = 'users';
}
