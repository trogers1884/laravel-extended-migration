<?php

namespace Trogers1884\ExtendedMigration\Tests\Unit\Console\Commands;

use Trogers1884\ExtendedMigration\Console\Commands\SchemaMigrateCommand;

class SchemaMigrateCommandTest extends BaseMigrationCommandTest
{
    protected SchemaMigrateCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = $this->createCommand(SchemaMigrateCommand::class);
    }

    public function testMigrateSingleSchema(): void
    {
        $schema = 'test_schema';
        $pendingMigrations = ['2025_02_22_000001_test_migration.php'];

        $this->expectSchemaExists($schema);

        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn([
                $schema => [
                    'pending' => $pendingMigrations,
                    'dependencies' => [],
                    'can_run' => true,
                ]
            ]);

        $this->runner->shouldReceive('runSchemaMigrations')
            ->with($schema, $pendingMigrations, false)
            ->once()
            ->andReturn($pendingMigrations);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains("Running migrations for schema: {$schema}");
    }

    public function testMigrateWithPretend(): void
    {
        $schema = 'test_schema';
        $pendingMigrations = ['2025_02_22_000001_test_migration.php'];

        $this->expectSchemaExists($schema);

        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn([
                $schema => [
                    'pending' => $pendingMigrations,
                    'dependencies' => [],
                    'can_run' => true,
                ]
            ]);

        $this->runner->shouldReceive('runSchemaMigrations')
            ->with($schema, $pendingMigrations, true)
            ->once()
            ->andReturn($pendingMigrations);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema,
            '--pretend' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains("Simulating migrations for schema: {$schema}");
    }

    public function testMigrateAllSchemas(): void
    {
        $status = [
            'schema1' => [
                'pending' => ['migration1.php'],
                'dependencies' => [],
                'can_run' => true,
            ],
            'schema2' => [
                'pending' => ['migration2.php'],
                'dependencies' => ['schema1'],
                'can_run' => true,
            ],
        ];

        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn($status);

        $this->runner->shouldReceive('runAllSchemaMigrations')
            ->with(false)
            ->once()
            ->andReturn([
                'schema1' => ['migration1.php'],
                'schema2' => ['migration2.php'],
            ]);

        $exitCode = $this->executeCommand($this->command, [
            '--all' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains('Running migrations for all schemas');
    }

    public function testCannotMigrateWhenDependenciesNotSatisfied(): void
    {
        $schema = 'test_schema';
        $pendingMigrations = ['2025_02_22_000001_test_migration.php'];

        $this->expectSchemaExists($schema);

        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn([
                $schema => [
                    'pending' => $pendingMigrations,
                    'dependencies' => ['dep_schema'],
                    'can_run' => false,
                ]
            ]);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertOutputContains('Cannot run migrations - dependencies not satisfied');
    }

    public function testNothingToMigrate(): void
    {
        $schema = 'test_schema';

        $this->expectSchemaExists($schema);

        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn([]);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains('Nothing to migrate');
    }

    public function testRequiresSchemaOrAllOption(): void
    {
        $exitCode = $this->executeCommand($this->command, []);

        $this->assertEquals(1, $exitCode);
        $this->assertOutputContains('Please specify a schema or use --all option');
    }
}
