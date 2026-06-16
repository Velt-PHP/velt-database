<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Database\ConnectionFactory;
use Velt\Database\DatabaseManager;
use Velt\Database\DB;
use Velt\Database\Tests\Fakes\ArrayConfigRepository;

final class QueryBuilderTest extends TestCase
{
    use RequiresSqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireSqlite();

        DB::setManager(new DatabaseManager(new ArrayConfigRepository([
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:'],
                ],
            ],
        ]), new ConnectionFactory()));

        DB::statement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL)');
        DB::table('users')->insert(['name' => 'Ada', 'email' => 'ada@example.com']);
        DB::table('users')->insert(['name' => 'Linus', 'email' => 'linus@example.com']);
    }

    protected function tearDown(): void
    {
        DB::clearManager();
        DB::clearCache();
        parent::tearDown();
    }

    public function test_select_where_order_limit_and_first(): void
    {
        $row = DB::table('users')
            ->select('id', 'name')
            ->where('email', 'ada@example.com')
            ->orderBy('id')
            ->limit(1)
            ->first();

        self::assertIsArray($row);
        self::assertSame('Ada', $row['name']);
    }

    public function test_update_and_delete(): void
    {
        $updated = DB::table('users')->where('email', 'ada@example.com')->update(['name' => 'Ada Lovelace']);
        $deleted = DB::table('users')->where('email', 'linus@example.com')->delete();
        $rows = DB::table('users')->orderBy('id')->get();

        self::assertSame(1, $updated);
        self::assertSame(1, $deleted);
        self::assertCount(1, $rows);
        self::assertSame('Ada Lovelace', $rows[0]['name']);
    }
}
