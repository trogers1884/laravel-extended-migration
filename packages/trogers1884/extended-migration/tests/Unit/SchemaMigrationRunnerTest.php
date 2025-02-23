<?php

namespace Trogers1884\ExtendedMigration\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;
use Illuminate\Database\Migrations\Migrator;
use Trogers1884\ExtendedMigration\Contracts\SchemaManagerInterface;
use Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRepositoryInterface;
use Trogers1884\ExtendedMigration\Database\Migrations\SchemaMigrationRunner;

class SchemaMigrationRunnerTest extends TestCase
{
    protected $migrator;
    protected $schemaManager;
    protected $repository;
    protected $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrator = Mockery::mock(Migrator::class);
        $this->schemaManager = Mockery::mock(SchemaManagerInterface::class);
        $this->repository = Mockery::mock(SchemaMigrationRepositoryInterface::class);

        $this->runner = new SchemaMigrationRunner(
            $this->migrator,
            $this->schemaManager,
            $this->repository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRunSchemaMigrationsChecksDependencies(): void
    {
        $schema = 'test_schema';
        $paths = ['2025_02_22_000001_test_migration.php'];

        $this->schemaManager->shouldReceive('hasSchema')
            ->with($schema)
            ->once()
            ->andReturn(true);

        $this->schemaManager->shouldReceive('getDependencies')
            ->with($schema)
            ->once()
            ->andReturn(['dep_schema']);

        $this->repository->shouldReceive('getPendingMigrations')
            ->with('dep_schema')
            ->once()
            ->andReturn([]);

        $this->schemaManager->shouldReceive('getPath')
            ->with($schema)
            ->once()
            ->andReturn('/path/to/migrations');

        $this->repository->shouldReceive('getNextBatchNumber')
            ->with($schema)
            ->once()
            ->andReturn(1);

        $this->migrator->shouldReceive('run')
            ->once()
            ->with(['/path/to/migrations/2025_02_22_000001_test_migration.php'], ['pretend' => false]);

        $this->repository->shouldReceive('log')
            ->with($schema, $paths[0], 1)
            ->once();

        $result = $this->runner->runSchemaMigrations($schema, $paths);
        $this->assertEquals($paths, $result);
    }

    public function testRunSchemaMigrationsFailsWithUnmetDependencies(): void
    {
        $schema = 'test_schema';
        $paths = ['2025_02_22_000001_test_migration.php'];

        $this->schemaManager->shouldReceive('hasSchema')
            ->with($schema)
            ->once()
            ->andReturn(true);

        $this->schemaManager->shouldReceive('getDependencies')
            ->with($schema)
            ->once()
            ->andReturn(['dep_schema']);

        $this->repository->shouldReceive('getPendingMigrations')
            ->with('dep_schema')
            ->once()
            ->andReturn(['pending_migration.php']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Schema 'test_schema' has unsatisfied dependencies.");

        $this->runner->runSchemaMigrations($schema, $paths);
    }

    public function testRollbackSchemaMigrations(): void
    {
        $schema = 'test_schema';
        $migrations = ['2025_02_22_000001_test_migration.php'];

        $this->schemaManager->shouldReceive('hasSchema')
            ->with($schema)
            ->once()
            ->andReturn(true);

        $this->schemaManager->shouldReceive('getPath')
            ->with($schema)
            ->once()
            ->andReturn('/path/to/migrations');

        $this->migrator->shouldReceive('rollback')
            ->once()
            ->with(['/path/to/migrations/2025_02_22_000001_test_migration.php'], ['pretend' => false]);

        $this->repository->shouldReceive('delete')
            ->with($schema, $migrations[0])
            ->once();

        $result = $this->runner->rollbackSchemaMigrations($schema, $migrations);
        $this->assertEquals($migrations, $result);
    }

    public function testResolveMigrationOrderChecksDependencies(): void
    {
        $schema = 'test_schema';
        $migrations = [
            '2025_02_22_000001_test_migration.php',
            '2025_02_22_000002_test_migration.php',
        ];

        $this->schemaManager->shouldReceive('getDependencies')
            ->with($schema)
            ->once()
            ->andReturn(['dep_schema']);

        $this->repository->shouldReceive('getPendingMigrations')
            ->with('dep_schema')
            ->once()
            ->andReturn([]);

        $result = $this->runner->resolveMigrationOrder($schema, $migrations);
        $this->assertEquals(sort($migrations), sort($result));
    }

    public function testResetSchema(): void
    {
        $schema = 'test_schema';
        $migrations = [
            '2025_02_22_000002_test_migration.php',
            '2025_02_22_000001_test_migration.php',
        ];

        $this->repository->shouldReceive('getMigrationHistory')
            ->with($schema)
            ->once()
            ->andReturn($migrations);

        $this->schemaManager->shouldReceive('hasSchema')
            ->with($schema)
            ->once()
            ->andReturn(true);

        $this->schemaManager->shouldReceive('getPath')
            ->with($schema)
            ->times(2)
            ->andReturn('/path/to/migrations');

        $this->migrator->shouldReceive('rollback')
            ->twice();

        $this->repository->shouldReceive('delete')
            ->twice();

        $result = $this->runner->resetSchema($schema);
        $this->assertEquals(array_reverse($migrations), $result);
    }

    public function testRunAllSchemaMigrations(): void
    {
        $schemas = [
            'test_schema1' => [
                'name' => 'test_schema1',
                'dependencies' => [],
            ],
            'test_schema2' => [
                'name' => 'test_schema2',
                'dependencies' => ['test_schema1'],
            ],
        ];

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn($schemas);

        $this->repository->shouldReceive('getPendingMigrations')
            ->with('test_schema1')
            ->once()
            ->andReturn(['migration1.php']);

        $this->repository->shouldReceive('getPendingMigrations')
            ->with('test_schema2')
            ->once()
            ->andReturn(['migration2.php']);

        $this->schemaManager->shouldReceive('hasSchema')
            ->twice()
            ->andReturn(true);

        $this->schemaManager->shouldReceive('getDependencies')
            ->twice()
            ->andReturn([]);

        $this->schemaManager->shouldReceive('getPath')
            ->twice()
            ->andReturn('/path/to/migrations');

        $this->repository->shouldReceive('getNextBatchNumber')
            ->twice()
            ->andReturn(1);

        $this->migrator->shouldReceive('run')
            ->twice();

        $this->repository->shouldReceive('log')
            ->twice();

        $result = $this->runner->runAllSchemaMigrations();
        $this->assertArrayHasKey('test_schema1', $result);
        $this->assertArrayHasKey('test_schema2', $result);
    }

    public function testRollbackAllSchemas(): void
    {
        $schemas = [
            'test_schema1' => [
                'name' => 'test_schema1',
                'dependencies' => [],
            ],
            'test_schema2' => [
                'name' => 'test_schema2',
                'dependencies' => ['test_schema1'],
            ],
        ];

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn($schemas);

        $this->repository->shouldReceive('getLast')
            ->twice()
            ->andReturn(['migration.php']);

        $this->schemaManager->shouldReceive('hasSchema')
            ->twice()
            ->andReturn(true);

        $this->schemaManager->shouldReceive('getPath')
            ->twice()
            ->andReturn('/path/to/migrations');

        $this->migrator->shouldReceive('rollback')
            ->twice();

        $this->repository->shouldReceive('delete')
            ->twice();

        $result = $this->runner->rollbackAllSchemas();
        $this->assertArrayHasKey('test_schema2', $result);
        $this->assertArrayHasKey('test_schema1', $result);
    }

    public function testGetPendingMigrationStatus(): void
    {
        $schemas = [
            'test_schema1' => [
                'name' => 'test_schema1',
                'dependencies' => [],
            ],
            'test_schema2' => [
                'name' => 'test_schema2',
                'dependencies' => ['test_schema1'],
            ],
        ];

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn($schemas);

        $this->repository->shouldReceive('getPendingMigrations')
            ->twice()
            ->andReturn(['pending_migration.php']);

        $this->schemaManager->shouldReceive('getDependencies')
            ->with('test_schema1')
            ->once()
            ->andReturn([]);

        $this->schemaManager->shouldReceive('getDependencies')
            ->with('test_schema2')
            ->once()
            ->andReturn(['test_schema1']);

        $status = $this->runner->getPendingMigrationStatus();

        $this->assertArrayHasKey('test_schema1', $status);
        $this->assertArrayHasKey('test_schema2', $status);
        $this->assertTrue($status['test_schema1']['can_run']);
    }

    public function testTransactionControl(): void
    {
        $this->assertTrue($this->runner->isUsingTransactions());

        $this->runner->useTransactions(false);
        $this->assertFalse($this->runner->isUsingTransactions());

        $this->runner->useTransactions(true);
        $this->assertTrue($this->runner->isUsingTransactions());
    }
}
