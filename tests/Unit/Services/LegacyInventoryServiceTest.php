<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\LegacyInventorySnapshot;
use App\Services\LegacyInventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use PDOException;
use RuntimeException;
use SplFileInfo;
use Tests\TestCase;

final class LegacyInventoryServiceTest extends TestCase
{
    // -----------------------------------------------------------------------
    //  2.1  Happy path
    // -----------------------------------------------------------------------

    public function test_build_snapshot_returns_complete_snapshot(): void
    {
        $modelClass = $this->uniqueModelName();

        $queryMock = Mockery::mock(Builder::class);
        $queryMock->shouldReceive('count')->once()->andReturn(42);
        Mockery::mock('alias:' . $modelClass, Model::class)
            ->shouldReceive('query')->once()->andReturn($queryMock);

        $this->mockDatabaseConnection(true, 'pgsql', 'checkplanilha');
        $this->mockConfig([
            'legacy.root_path' => '/var/www/checkplanilha',
            'legacy.public_url' => 'http://legacy.test',
            'legacy.paths' => [
                'controllers' => 'app',
                'services' => 'app/Services',
            ],
            'legacy.modules' => [
                [
                    'key' => 'test-module',
                    'title' => 'Test Module',
                    'description' => 'Test module description',
                    'category' => 'Estrutura',
                    'tone' => 'structure',
                    'legacy_path' => '/test',
                    'target' => 'app/Test.php',
                    'model' => $modelClass,
                ],
            ],
        ]);
        $this->mockFileAllFiles([
            '/var/www/checkplanilha/app' => 2,
            '/var/www/checkplanilha/app/Services' => 1,
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertInstanceOf(LegacyInventorySnapshot::class, $result);
        $this->assertSame('/var/www/checkplanilha', $result->legacyRootPath);
        $this->assertSame('http://legacy.test', $result->legacyPublicUrl);
        $this->assertTrue($result->databaseReachable);
        $this->assertSame('pgsql', $result->databaseDriver);
        $this->assertSame('checkplanilha', $result->databaseName);
        $this->assertNull($result->databaseError);
        $this->assertSame(['controllers' => 2, 'services' => 1], $result->architectureCounts);
        $this->assertCount(1, $result->modules);
        $this->assertSame(42, $result->modules[0]->records);
    }

    // -----------------------------------------------------------------------
    //  2.2  Database unreachable (PDO exception)
    // -----------------------------------------------------------------------

    public function test_build_snapshot_when_database_unreachable_returns_graceful_response(): void
    {
        $modelClass1 = $this->uniqueModelName();
        $modelClass2 = $this->uniqueModelName();

        Mockery::mock('alias:' . $modelClass1, Model::class)
            ->shouldReceive('query')->never();
        Mockery::mock('alias:' . $modelClass2, Model::class)
            ->shouldReceive('query')->never();

        $this->mockDatabaseUnreachable(new PDOException('Connection refused'));
        $this->mockConfig([
            'legacy.root_path' => '/var/www/checkplanilha',
            'legacy.public_url' => 'http://legacy.test',
            'legacy.paths' => [
                'controllers' => 'app',
            ],
            'legacy.modules' => [
                [
                    'key' => 'mod-a',
                    'title' => 'Module A',
                    'description' => 'First module',
                    'category' => 'Estrutura',
                    'tone' => 'structure',
                    'legacy_path' => '/a',
                    'target' => 'app/A.php',
                    'model' => $modelClass1,
                ],
                [
                    'key' => 'mod-b',
                    'title' => 'Module B',
                    'description' => 'Second module',
                    'category' => 'Inventário',
                    'tone' => 'inventory',
                    'legacy_path' => '/b',
                    'target' => 'app/B.php',
                    'model' => $modelClass2,
                ],
            ],
        ]);
        $this->mockFileAllFiles(['/var/www/checkplanilha/app' => 3]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertFalse($result->databaseReachable);
        $this->assertNull($result->databaseDriver);
        $this->assertNull($result->databaseName);
        $this->assertSame('Connection refused', $result->databaseError);
        $this->assertCount(2, $result->modules);
        $this->assertNull($result->modules[0]->records);
        $this->assertNull($result->modules[1]->records);
    }

    // -----------------------------------------------------------------------
    //  2.3  Generic Throwable from database probe
    // -----------------------------------------------------------------------

    public function test_build_snapshot_catches_generic_exceptions_from_database_probe(): void
    {
        $this->mockDatabaseUnreachable(new RuntimeException('Something went wrong'));
        $this->mockConfig([
            'legacy.paths' => [],
            'legacy.modules' => [],
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertFalse($result->databaseReachable);
        $this->assertSame('Something went wrong', $result->databaseError);
    }

    // -----------------------------------------------------------------------
    //  2.4  Empty paths list
    // -----------------------------------------------------------------------

    public function test_build_snapshot_with_empty_paths_config(): void
    {
        $modelClass = $this->uniqueModelName();
        $queryMock = Mockery::mock(Builder::class);
        $queryMock->shouldReceive('count')->once()->andReturn(5);
        Mockery::mock('alias:' . $modelClass, Model::class)
            ->shouldReceive('query')->once()->andReturn($queryMock);

        $this->mockDatabaseConnection(true, 'mysql', 'test');
        $this->mockConfig([
            'legacy.paths' => [],
            'legacy.modules' => [
                [
                    'key' => 'single',
                    'title' => 'Single',
                    'description' => 'Just one',
                    'legacy_path' => '/s',
                    'target' => 'app/S.php',
                    'model' => $modelClass,
                ],
            ],
        ]);

        File::shouldReceive('allFiles')->never();

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertSame([], $result->architectureCounts);
    }

    // -----------------------------------------------------------------------
    //  2.5  Missing directory returns zero count
    // -----------------------------------------------------------------------

    public function test_build_snapshot_with_missing_directory_returns_zero_count(): void
    {
        $modelClass = $this->uniqueModelName();
        $queryMock = Mockery::mock(Builder::class);
        $queryMock->shouldReceive('count')->once()->andReturn(0);
        Mockery::mock('alias:' . $modelClass, Model::class)
            ->shouldReceive('query')->once()->andReturn($queryMock);

        $this->mockDatabaseConnection(true, 'sqlite', 'memory');

        // Set up config manually to avoid duplicate expectations with mockConfig
        Config::shouldReceive('get')
            ->with('legacy.root_path', null)
            ->once()
            ->andReturn('/var/www/checkplanilha');
        Config::shouldReceive('get')
            ->with('legacy.public_url', null)
            ->once()
            ->andReturn('http://legacy.test');
        Config::shouldReceive('get')
            ->with('legacy.paths', [])
            ->once()
            ->andReturn([
                'existing' => 'app',
                'missing' => 'nonexistent_dir',
            ]);
        Config::shouldReceive('get')
            ->with('legacy.modules', [])
            ->once()
            ->andReturn([
                [
                    'key' => 'dummy',
                    'title' => 'Dummy',
                    'description' => 'Dummy module',
                    'legacy_path' => '/d',
                    'target' => 'app/D.php',
                    'model' => $modelClass,
                ],
            ]);

        // 'app' exists on this filesystem; 'nonexistent_dir' does not
        File::shouldReceive('allFiles')
            ->with('/var/www/checkplanilha/app')
            ->once()
            ->andReturn([new SplFileInfo(__FILE__), new SplFileInfo(__FILE__)]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertSame(['existing' => 2, 'missing' => 0], $result->architectureCounts);
    }

    // -----------------------------------------------------------------------
    //  2.6  Empty modules list
    // -----------------------------------------------------------------------

    public function test_build_snapshot_with_empty_modules_config(): void
    {
        $this->mockDatabaseConnection(true, 'pgsql', 'db');
        $this->mockConfig([
            'legacy.root_path' => '/var/www/checkplanilha',
            'legacy.paths' => ['test' => 'app'],
            'legacy.modules' => [],
        ]);
        $this->mockFileAllFiles(['/var/www/checkplanilha/app' => 1]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertSame([], $result->modules);
    }

    // -----------------------------------------------------------------------
    //  2.7  Invalid model class (does not extend Eloquent Model)
    // -----------------------------------------------------------------------

    public function test_build_snapshot_with_invalid_model_class_returns_null_records(): void
    {
        $this->mockDatabaseConnection(true, 'pgsql', 'db');
        $this->mockConfig([
            'legacy.paths' => [],
            'legacy.modules' => [
                [
                    'key' => 'bad-model',
                    'title' => 'Bad Model',
                    'description' => 'Not an Eloquent model',
                    'legacy_path' => '/bad',
                    'target' => 'app/Bad.php',
                    'model' => \stdClass::class,
                ],
            ],
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertCount(1, $result->modules);
        $this->assertNull($result->modules[0]->records);
    }

    // -----------------------------------------------------------------------
    //  2.8  Model query throws exception
    // -----------------------------------------------------------------------

    public function test_build_snapshot_when_model_query_throws_exception_returns_null_records(): void
    {
        $modelClass = $this->uniqueModelName();
        $queryMock = Mockery::mock(Builder::class);
        $queryMock->shouldReceive('count')->once()->andThrow(new RuntimeException('Query crashed'));
        Mockery::mock('alias:' . $modelClass, Model::class)
            ->shouldReceive('query')->once()->andReturn($queryMock);

        $this->mockDatabaseConnection(true, 'pgsql', 'db');
        $this->mockConfig([
            'legacy.paths' => [],
            'legacy.modules' => [
                [
                    'key' => 'faulty',
                    'title' => 'Faulty',
                    'description' => 'Faulty model query',
                    'legacy_path' => '/f',
                    'target' => 'app/F.php',
                    'model' => $modelClass,
                ],
            ],
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertNull($result->modules[0]->records);
    }

    // -----------------------------------------------------------------------
    //  2.9  Module with scope applied
    // -----------------------------------------------------------------------

    public function test_build_snapshot_with_scoped_module(): void
    {
        $modelClass = $this->uniqueModelName();

        $modelWithScope = new class extends Model
        {
            public function scopeNewProducts($query): void {}
        };

        $queryMock = Mockery::mock(Builder::class);
        $queryMock->shouldReceive('getModel')->once()->andReturn($modelWithScope);
        $queryMock->shouldReceive('newProducts')->once()->andReturnSelf();
        $queryMock->shouldReceive('count')->once()->andReturn(15);
        Mockery::mock('alias:' . $modelClass, Model::class)
            ->shouldReceive('query')->once()->andReturn($queryMock);

        $this->mockDatabaseConnection(true, 'pgsql', 'db');
        $this->mockConfig([
            'legacy.paths' => [],
            'legacy.modules' => [
                [
                    'key' => 'scoped',
                    'title' => 'Scoped Module',
                    'description' => 'With scope',
                    'legacy_path' => '/s',
                    'target' => 'app/S.php',
                    'model' => $modelClass,
                    'scope' => 'newProducts',
                ],
            ],
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertSame(15, $result->modules[0]->records);
    }

    // -----------------------------------------------------------------------
    //  2.10  Nonexistent scope on model
    // -----------------------------------------------------------------------

    public function test_build_snapshot_with_nonexistent_scope_skips_scope_gracefully(): void
    {
        $modelClass = $this->uniqueModelName();

        $modelWithoutScope = new class extends Model {};

        $queryMock = Mockery::mock(Builder::class);
        $queryMock->shouldReceive('getModel')->once()->andReturn($modelWithoutScope);
        $queryMock->shouldReceive('count')->once()->andReturn(10);
        Mockery::mock('alias:' . $modelClass, Model::class)
            ->shouldReceive('query')->once()->andReturn($queryMock);

        $this->mockDatabaseConnection(true, 'pgsql', 'db');
        $this->mockConfig([
            'legacy.paths' => [],
            'legacy.modules' => [
                [
                    'key' => 'no-scope',
                    'title' => 'No Scope',
                    'description' => 'Scope method missing',
                    'legacy_path' => '/n',
                    'target' => 'app/N.php',
                    'model' => $modelClass,
                    'scope' => 'nonexistent',
                ],
            ],
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertSame(10, $result->modules[0]->records);
    }

    // -----------------------------------------------------------------------
    //  2.11  Module without a model key
    // -----------------------------------------------------------------------

    public function test_build_snapshot_with_module_without_model_returns_null_records(): void
    {
        $this->mockDatabaseConnection(true, 'pgsql', 'db');
        $this->mockConfig([
            'legacy.paths' => [],
            'legacy.modules' => [
                [
                    'key' => 'no-model',
                    'title' => 'No Model',
                    'description' => 'This module has no model key',
                    'legacy_path' => '/no-model',
                    'target' => 'app/NoModel.php',
                    // no 'model' key — like the 'reports' module
                ],
            ],
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertNull($result->modules[0]->records);
    }

    // -----------------------------------------------------------------------
    //  2.12  Default values for optional module fields
    // -----------------------------------------------------------------------

    public function test_build_snapshot_uses_defaults_for_optional_module_fields(): void
    {
        $this->mockDatabaseConnection(true, 'pgsql', 'db');
        $this->mockConfig([
            'legacy.paths' => [],
            'legacy.modules' => [
                [
                    'key' => 'defaults',
                    'title' => 'Defaults Test',
                    'description' => 'No category or tone',
                    'legacy_path' => '/d',
                    'target' => 'app/D.php',
                ],
            ],
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertSame('Estrutura', $result->modules[0]->category);
        $this->assertSame('structure', $result->modules[0]->tone);
    }

    // -----------------------------------------------------------------------
    //  2.13  Trailing slash stripped from public_url
    // -----------------------------------------------------------------------

    public function test_build_snapshot_trims_trailing_slash_from_public_url(): void
    {
        $this->mockDatabaseConnection(true, 'pgsql', 'db');
        $this->mockConfig([
            'legacy.public_url' => 'http://legacy.test/',
            'legacy.paths' => [],
            'legacy.modules' => [],
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertSame('http://legacy.test', $result->legacyPublicUrl);
    }

    // -----------------------------------------------------------------------
    //  2.14  Empty root_path
    // -----------------------------------------------------------------------

    public function test_build_snapshot_with_empty_root_path(): void
    {
        $this->mockDatabaseConnection(true, 'pgsql', 'db');
        $this->mockConfig([
            'legacy.root_path' => '',
            'legacy.paths' => ['controllers' => 'app/Controllers'],
            'legacy.modules' => [],
        ]);
        File::shouldReceive('allFiles')->never();

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertInstanceOf(LegacyInventorySnapshot::class, $result);
        $this->assertSame('', $result->legacyRootPath);
        // When root_path is empty, the full path becomes '/app/Controllers'
        // which typically doesn't exist, so count is 0 without calling allFiles
    }

    // -----------------------------------------------------------------------
    //  2.15  Database unreachable — all module records null (covered by 2.2)
    //        Additional test: DB error with non-PDO exception type
    // -----------------------------------------------------------------------

    public function test_probe_database_catches_non_pdo_exception(): void
    {
        $this->mockDatabaseUnreachable(new \Error('Call to undefined method'));
        $this->mockConfig([
            'legacy.paths' => [],
            'legacy.modules' => [],
        ]);

        $result = (new LegacyInventoryService())->buildSnapshot();

        $this->assertFalse($result->databaseReachable);
        $this->assertSame('Call to undefined method', $result->databaseError);
    }

    // =======================================================================
    //  Helpers
    // =======================================================================

    /**
     * Generate a unique model class name for Mockery alias mocking.
     */
    private function uniqueModelName(): string
    {
        return 'LegacyInvTestModel_' . str_replace('.', '', uniqid('', true));
    }

    /**
     * Mock Config::get() for the four legacy-service keys.
     */
    private function mockConfig(array $values): void
    {
        $defaults = [
            'legacy.root_path' => '/var/www/legacy',
            'legacy.public_url' => 'http://legacy.test',
            'legacy.paths' => [],
            'legacy.modules' => [],
        ];

        $merged = array_merge($defaults, $values);

        Config::shouldReceive('get')
            ->with('legacy.root_path', null)
            ->once()
            ->andReturn($merged['legacy.root_path']);
        Config::shouldReceive('get')
            ->with('legacy.public_url', null)
            ->once()
            ->andReturn($merged['legacy.public_url']);
        Config::shouldReceive('get')
            ->with('legacy.paths', [])
            ->once()
            ->andReturn($merged['legacy.paths']);
        Config::shouldReceive('get')
            ->with('legacy.modules', [])
            ->once()
            ->andReturn($merged['legacy.modules']);
    }

    /**
     * Mock DB::connection() returning a reachable connection.
     */
    private function mockDatabaseConnection(bool $reachable, ?string $driver, ?string $name): void
    {
        $connectionMock = Mockery::mock();
        $connectionMock->shouldReceive('getPdo')->once()->andReturn(true);
        $connectionMock->shouldReceive('getDriverName')->once()->andReturn($driver);
        $connectionMock->shouldReceive('getDatabaseName')->once()->andReturn($name);

        DB::shouldReceive('connection')->once()->andReturn($connectionMock);
    }

    /**
     * Mock DB::connection()->getPdo() to throw an exception.
     */
    private function mockDatabaseUnreachable(\Throwable $exception): void
    {
        $connectionMock = Mockery::mock();
        $connectionMock->shouldReceive('getPdo')->once()->andThrow($exception);

        DB::shouldReceive('connection')->once()->andReturn($connectionMock);
    }

    /**
     * Mock File::allFiles() for given paths with expected file counts.
     *
     * @param array<string, int> $pathToFileCount
     */
    private function mockFileAllFiles(array $pathToFileCount): void
    {
        foreach ($pathToFileCount as $path => $count) {
            $files = array_fill(0, $count, new SplFileInfo(__FILE__));
            File::shouldReceive('allFiles')
                ->with($path)
                ->once()
                ->andReturn($files);
        }
    }
}
