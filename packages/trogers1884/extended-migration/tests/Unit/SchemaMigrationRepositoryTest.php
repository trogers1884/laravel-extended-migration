<?php

namespace Trogers1884\ExtendedMigration\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Trogers1884\ExtendedMigration\Database\Migrations\SchemaMigrationRepository;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\PostgresBuilder;
use InvalidArgumentException;
use Mockery;

class SchemaMigrationRepositoryTest extends TestCase
{
    protected SchemaMigrationRepository $repository;
    protected $connection;
    protected $resolver;
    protected $schema;
    protected string $table = 'migrations';

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks
        $this->connection = Mockery::mock(Connection::class);
        $this->resolver = Mockery::mock(ConnectionResolverInterface::class);
        $this->schema = Mockery::mock(PostgresBuilder::class);

        // Setup basic mock expectations
        $this->resolver->shouldReceive('connection')
            ->andReturn($this->connection);
        $this->connection->shouldReceive('getSchemaBuilder')
            ->andReturn($this->schema);

        // Create repository instance
        $this->repository = new SchemaMigrationRepository($this->resolver, $this->table);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRepositoryExistsChecksCorrectSchema(): void
    {
        $schema = 'test_schema';
        $tableName = $schema . '.' . $this->table;

        $this->schema->shouldReceive('hasTable')
            ->with($tableName)
            ->once()
            ->andReturn(true);

        $this->assertTrue($this->repository->repositoryExists($schema));
    }

    public function testCreateRepositoryMakesCorrectTable(): void
    {
        $schema = 'test_schema';
        $tableName = $schema . '.' . $this->table;

        $this->schema->shouldReceive('create')
            ->with($tableName, Mockery::on(function ($callback) {
                $table = Mockery::mock('Illuminate\Database\Schema\Blueprint');
                $table->shouldReceive('increments')->with('id')->once();
                $table->shouldReceive('string')->with('migration')->once()->andReturn($table);
                $table->shouldReceive('unique')->once();
                $table->shouldReceive('integer')->with('batch')->once();
                $table->shouldReceive('timestamp')->with('executed_at')->once()->andReturn($table);
                $table->shouldReceive('useCurrent')->once();

                $callback($table);
                return true;
            }))
            ->once();

        $this->repository->createRepository($schema);
    }

    public function testGetMigrationsReturnsCorrectSchemaRecords(): void
    {
        $schema = 'test_schema';
        $query = Mockery::mock('Illuminate\Database\Query\Builder');

        $this->connection->shouldReceive('table')
            ->with($this->table)
            ->once()
            ->andReturn($query);

        $query->shouldReceive('where')
            ->with('migration', 'like', $schema . '/%')
            ->once()
            ->andReturn($query);
        $query->shouldReceive('orderBy')
            ->with('batch', 'asc')
            ->once()
            ->andReturn($query);
        $query->shouldReceive('orderBy')
            ->with('migration', 'asc')
            ->once()
            ->andReturn($query);
        $query->shouldReceive('get')
            ->once()
            ->andReturn(collect([]));

        $this->assertIsArray($this->repository->getMigrations($schema));
    }

    public function testLogMigrationAddsRecord(): void
    {
        $schema = 'test_schema';
        $file = 'test_migration';
        $batch = 1;
        $query = Mockery::mock('Illuminate\Database\Query\Builder');

        $this->connection->shouldReceive('table')
            ->with($this->table)
            ->once()
            ->andReturn($query);

        $query->shouldReceive('insert')
            ->with([
                'migration' => $schema . '/' . $file,
                'batch' => $batch
            ])
            ->once();

        $this->repository->log($schema, $file, $batch);
    }

    public function testDeleteMigrationRemovesRecord(): void
    {
        $schema = 'test_schema';
        $file = 'test_migration';
        $query = Mockery::mock('Illuminate\Database\Query\Builder');

        $this->connection->shouldReceive('table')
            ->with($this->table)
            ->once()
            ->andReturn($query);

        $query->shouldReceive('where')
            ->with('migration', $schema . '/' . $file)
            ->once()
            ->andReturn($query);
        $query->shouldReceive('delete')
            ->once();

        $this->repository->delete($schema, $file);
    }

    public function testGetNextBatchNumberIncrements(): void
    {
        $schema = 'test_schema';
        $query = Mockery::mock('Illuminate\Database\Query\Builder');

        $this->connection->shouldReceive('table')
            ->with($this->table)
            ->once()
            ->andReturn($query);

        $query->shouldReceive('where')
            ->with('migration', 'like', $schema . '/%')
            ->once()
            ->andReturn($query);
        $query->shouldReceive('max')
            ->with('batch')
            ->once()
            ->andReturn(1);

        $this->assertEquals(2, $this->repository->getNextBatchNumber($schema));
    }

    public function testGetLastReturnsLatestBatch(): void
    {
        $schema = 'test_schema';
        $query = Mockery::mock('Illuminate\Database\Query\Builder');

        $this->connection->shouldReceive('table')
            ->with($this->table)
            ->times(2)
            ->andReturn($query);

        $query->shouldReceive('where')
            ->with('migration', 'like', $schema . '/%')
            ->twice()
            ->andReturn($query);
        $query->shouldReceive('max')
            ->with('batch')
            ->once()
            ->andReturn(1);
        $query->shouldReceive('where')
            ->with('batch', 1)
            ->once()
            ->andReturn($query);
        $query->shouldReceive('orderBy')
            ->with('migration', 'desc')
            ->once()
            ->andReturn($query);
        $query->shouldReceive('get')
            ->once()
            ->andReturn(collect([]));

        $this->assertIsArray($this->repository->getLast($schema));
    }

    public function testGetSchemaStatusReturnsAllSchemas(): void
    {
        $query = Mockery::mock('Illuminate\Database\Query\Builder');
        $migrations = collect([
            (object)[
                'migration' => 'test_schema/migration1',
                'batch' => 1,
                'executed_at' => '2025-02-22 12:00:00'
            ],
            (object)[
                'migration' => 'test_schema/migration2',
                'batch' => 2,
                'executed_at' => '2025-02-22 12:30:00'
            ]
        ]);

        $this->connection->shouldReceive('table')
            ->with($this->table)
            ->once()
            ->andReturn($query);

        $query->shouldReceive('orderBy')
            ->with('batch', 'asc')
            ->once()
            ->andReturn($query);
        $query->shouldReceive('orderBy')
            ->with('migration', 'asc')
            ->once()
            ->andReturn($query);
        $query->shouldReceive('get')
            ->once()
            ->andReturn($migrations);

        $status = $this->repository->getSchemaStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('test_schema', $status);
        $this->assertEquals(2, $status['test_schema']['total']);
        $this->assertEquals(2, $status['test_schema']['last_batch']);
        $this->assertEquals('test_schema/migration2', $status['test_schema']['last_migration']);
    }

    public function testDeleteRepositoryDropsTable(): void
    {
        $schema = 'test_schema';
        $tableName = $schema . '.' . $this->table;

        $this->schema->shouldReceive('dropIfExists')
            ->with($tableName)
            ->once();

        $this->repository->deleteRepository($schema);
    }
}
