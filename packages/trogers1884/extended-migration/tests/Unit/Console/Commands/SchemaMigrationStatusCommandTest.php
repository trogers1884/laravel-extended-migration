<?php

namespace Trogers1884\ExtendedMigration\Tests\Unit\Console\Commands;

use Trogers1884\ExtendedMigration\Console\Commands\SchemaMigrationStatusCommand;

class SchemaMigrationStatusCommandTest extends BaseMigrationCommandTest
{
    protected SchemaMigrationStatusCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = $this->createCommand(SchemaMigrationStatusCommand::class);
    }

    public function testShowSingleSchemaStatus(): void
    {
        $schema = 'test_schema';
        $status = [
            $schema => [
                'pending' => ['2025_02_22_000001_test_migration.php'],
                'dependencies' => ['dep_schema'],
                'can_run' => true,
            ]
        ];

        $this->expectSchemaExists($schema);

        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn($status);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains($schema);
        $this->assertOutputContains('dep_schema');
        $this->assertOutputContains('Pending Migrations:');
    }

    public function testShowSingleSchemaWithNoPendingMigrations(): void
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
        $this->assertOutputContains("No pending migrations for schema: {$schema}");
    }

    public function testShowAllSchemasStatus(): void
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
                'can_run' => false,
            ],
        ];

        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn($status);

        $exitCode = $this->executeCommand($this->command, []);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains('schema1');
        $this->assertOutputContains('schema2');
        $this->assertOutputContains('schema1');  // as dependency
    }

    public function testShowOnlyPendingMigrations(): void
    {
        $status = [
            'schema1' => [
                'pending' => ['migration1.php'],
                'dependencies' => [],
                'can_run' => true,
            ],
        ];

        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn($status);

        $exitCode = $this->executeCommand($this->command, [
            '--pending' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains('schema1');
    }

    public function testShowAllSchemasWhenNoPendingAndNotFiltered(): void
    {
        $schemas = [
            'schema1' => [
                'name' => 'schema1',
                'path' => '/path/to/schema1',
                'dependencies' => [],
            ],
            'schema2' => [
                'name' => 'schema2',
                'path' => '/path/to/schema2',
                'dependencies' => ['schema1'],
            ],
        ];

        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn([]);

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn($schemas);

        $exitCode = $this->executeCommand($this->command, []);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains('schema1');
        $this->assertOutputContains('schema2');
    }

    public function testShowNoPendingMigrationsMessage(): void
    {
        $this->runner->shouldReceive('getPendingMigrationStatus')
            ->once()
            ->andReturn([]);

        $exitCode = $this->executeCommand($this->command, [
            '--pending' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains('No pending migrations');
    }
}
