<?php

declare(strict_types=1);

namespace Velt\Database\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Database\ConnectionFactory;
use Velt\Database\DatabaseManager;
use Velt\Database\DB;
use Velt\Database\Migrations\Migrator;
use Velt\Database\Schema\Blueprint;
use Velt\Database\Schema\Schema;
use Velt\Database\Tests\Fakes\ArrayConfigRepository;

final class SchemaMigrationTest extends TestCase
{
    use RequiresSqlite;

    private string $migrationPath;

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

        $this->migrationPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'velt-migrations-' . bin2hex(random_bytes(4));
        mkdir($this->migrationPath);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->migrationPath . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->migrationPath)) {
            rmdir($this->migrationPath);
        }

        DB::clearManager();
        parent::tearDown();
    }

    public function test_schema_can_create_a_table(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('age');
            $table->timestamps();
        });

        DB::table('users')->insert(['name' => 'Ada', 'age' => 37]);
        $row = DB::table('users')->where('name', 'Ada')->first();

        self::assertIsArray($row);
        self::assertSame(37, (int) $row['age']);
    }

    public function test_migrator_runs_and_rolls_back_last_batch(): void
    {
        file_put_contents($this->migrationPath . DIRECTORY_SEPARATOR . '2026_01_01_000000_create_users.php', <<<'PHP'
<?php

use Velt\Database\Schema\Blueprint;
use Velt\Database\Schema\Schema;

return new class {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::drop('users');
    }
};
PHP);

        $migrator = new Migrator($this->migrationPath);

        self::assertSame(['2026_01_01_000000_create_users.php'], $migrator->migrate());
        DB::table('users')->insert(['name' => 'Ada']);
        self::assertSame(['2026_01_01_000000_create_users.php'], $migrator->rollback());

        $this->expectException(\PDOException::class);
        DB::table('users')->get();
    }
}
