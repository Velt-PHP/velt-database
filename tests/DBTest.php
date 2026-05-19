<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use Velt\Database\ConnectionFactory;
use Velt\Database\DatabaseManager;
use Velt\Database\DB;
use Velt\Database\Tests\Fakes\ArrayConfigRepository;

final class DBTest extends TestCase
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

        DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE)');
        DB::statement('INSERT INTO users (name, email) VALUES (?, ?)', ['Ada', 'ada@example.com']);
        DB::statement('INSERT INTO users (name, email) VALUES (?, ?)', ['Linus', 'linus@example.com']);
    }

    protected function tearDown(): void
    {
        DB::clearManager();
        parent::tearDown();
    }

    public function test_select_returns_multiple_rows(): void
    {
        $rows = DB::select('SELECT id, name FROM users WHERE id > ? ORDER BY id ASC', [0]);

        self::assertCount(2, $rows);
        self::assertSame('Ada', $rows[0]['name']);
        self::assertSame('Linus', $rows[1]['name']);
    }

    public function test_first_returns_one_row_or_null(): void
    {
        $row = DB::first('SELECT id, email FROM users WHERE email = ?', ['ada@example.com']);
        $missing = DB::first('SELECT id FROM users WHERE email = ?', ['missing@example.com']);

        self::assertIsArray($row);
        self::assertSame('ada@example.com', $row['email']);
        self::assertNull($missing);
    }

    public function test_statement_returns_affected_row_count(): void
    {
        $affected = DB::statement('UPDATE users SET name = ? WHERE email = ?', ['Ada Lovelace', 'ada@example.com']);
        $row = DB::first('SELECT name FROM users WHERE email = ?', ['ada@example.com']);

        self::assertSame(1, $affected);
        self::assertIsArray($row);
        self::assertSame('Ada Lovelace', $row['name']);
    }

    public function test_transaction_commits_when_callback_succeeds(): void
    {
        DB::transaction(function (): void {
            DB::statement('INSERT INTO users (name, email) VALUES (?, ?)', ['Grace', 'grace@example.com']);
        });

        $row = DB::first('SELECT email FROM users WHERE email = ?', ['grace@example.com']);

        self::assertIsArray($row);
        self::assertSame('grace@example.com', $row['email']);
    }

    public function test_transaction_rolls_back_when_callback_throws(): void
    {
        try {
            DB::transaction(function (): void {
                DB::statement('INSERT INTO users (name, email) VALUES (?, ?)', ['Rollback', 'rollback@example.com']);
                throw new RuntimeException('stop');
            });

            self::fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            self::assertSame('stop', $e->getMessage());
        }

        $row = DB::first('SELECT email FROM users WHERE email = ?', ['rollback@example.com']);
        self::assertNull($row);
    }
}
