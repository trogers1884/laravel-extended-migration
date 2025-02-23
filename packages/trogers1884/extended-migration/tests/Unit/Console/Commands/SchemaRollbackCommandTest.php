<?php

namespace Trogers1884\ExtendedMigration\Tests\Unit\Console\Commands;

use Trogers1884\ExtendedMigration\Console\Commands\SchemaRollbackCommand;

class SchemaRollbackCommandTest extends BaseMigrationCommandTest
{
    protected SchemaRollbackCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = $this->createCommand(SchemaRollbackCommand::class);
    }

    public function testRollbackSingleSchema(): void
    {
        $schema = 'test_schema';
        $migrations = ['2025_02_22_000001_test_migration.php'];

        $this->expectSchemaExists($schema);

        $this->runner->shouldReceive('rollbackSchemaMigrations')
            ->with($schema, null, false)
            ->once()
            ->andReturn($migrations);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema,
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains("Rolling back migrations for schema: {$schema}");
    }

    public function testRollbackWithPretend(): void
    {
        $schema = 'test_schema';
        $migrations = ['2025_02_22_000001_test_migration.php'];

        $this->expectSchemaExists($schema);

        $this->runner->shouldReceive('rollbackSchemaMigrations')
            ->with($schema, null, true)
            ->once()
            ->andReturn($migrations);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema,
            '--pretend' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains("Simulating rollback for schema: {$schema}");
    }

    public function testRollbackAllSchemas(): void
    {
        $schemas = [
            'schema1' => ['name' => 'schema1', 'dependencies' => []],
            'schema2' => ['name' => 'schema2', 'dependencies' => ['schema1']],
        ];

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn($schemas);

        $this->runner->shouldReceive('rollbackAllSchemas')
            ->with(false)
            ->once()
            ->andReturn([
                'schema2' => ['migration2.php'],
                'schema1' => ['migration1.php'],
            ]);

        $exitCode = $this->executeCommand($this->command, [
            '--all' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains('Rolling back migrations for all schemas');
    }

    public function testNothingToRollback(): void
    {
        $schema = 'test_schema';

        $this->expectSchemaExists($schema);

        $this->runner->shouldReceive('rollbackSchemaMigrations')
            ->with($schema, null, false)
            ->once()
            ->andReturn([]);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema,
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains('Nothing to rollback');
    }

    public function testRequiresSchemaOrAllOption(): void
    {
        $exitCode = $this->executeCommand($this->command, []);

        $this->assertEquals(1, $exitCode);
        $this->assertOutputContains('Please specify a schema or use --all option');
    }

    public function testRequiresConfirmationForDestructiveOperation(): void
    {
        $schema = 'test_schema';

        $this->expectSchemaExists($schema);

        // Don't set up any expectations for rollback since it should not be called

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertOutputMatches('/Are you sure you want to rollback/');
    }
}
