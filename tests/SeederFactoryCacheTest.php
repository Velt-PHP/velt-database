<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Database\Cache\FileDatabaseCache;
use Velt\Database\ConnectionFactory;
use Velt\Database\DatabaseManager;
use Velt\Database\DB;
use Velt\Database\Factories\Factory;
use Velt\Database\Seeders\Seeder;
use Velt\Database\Seeders\SeederRunner;
use Velt\Database\Tests\Fakes\ArrayConfigRepository;

final class SeederFactoryCacheTest extends TestCase
{
    use RequiresSqlite;

    private string $cachePath;

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

        $this->cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'velt-cache-' . bin2hex(random_bytes(4));
        DB::setCache(new FileDatabaseCache($this->cachePath));
    }

    protected function tearDown(): void
    {
        DB::clearManager();
        DB::clearCache();

        foreach (glob($this->cachePath . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->cachePath)) {
            rmdir($this->cachePath);
        }

        parent::tearDown();
    }

    public function test_seeder_can_fill_table(): void
    {
        (new SeederRunner())->run(new class extends Seeder {
            public function run(): void
            {
                DB::table('users')->insert(['name' => 'Ada', 'email' => 'ada@example.com']);
            }
        });

        self::assertSame('Ada', DB::table('users')->first()['name']);
    }

    public function test_factory_can_make_and_create_data(): void
    {
        $factory = new class extends Factory {
            public function definition(): array
            {
                return ['name' => 'Grace', 'email' => 'grace@example.com'];
            }

            protected function table(): string
            {
                return 'users';
            }
        };

        self::assertSame('Grace', $factory->make()['name']);
        $factory->create();

        self::assertSame('Grace', DB::table('users')->first()['name']);
    }

    public function test_query_results_can_be_cached_and_invalidated(): void
    {
        DB::table('users')->insert(['name' => 'Ada', 'email' => 'ada@example.com']);

        $first = DB::table('users')->remember(60)->get();
        DB::table('users')->insert(['name' => 'Linus', 'email' => 'linus@example.com']);
        $cached = DB::table('users')->remember(60)->get();

        DB::cache()->flush();
        $fresh = DB::table('users')->remember(60)->get();

        self::assertCount(1, $first);
        self::assertCount(1, $cached);
        self::assertCount(2, $fresh);
    }
}
