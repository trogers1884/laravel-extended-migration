<?php

namespace Trogers1884\ExtendedMigration\Tests\Unit\Console\Commands;

use Trogers1884\ExtendedMigration\Console\Commands\SchemaResetCommand;

class SchemaResetCommandTest extends BaseMigrationCommandTest
{
    protected SchemaResetCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = $this->createCommand(SchemaResetCommand::class);
    }

    public function testResetSchema(): void
    {
        $schema = 'test_schema';
        $migrations = [
            '2025_02_22_000001_test_migration.php',
            '2025_02_22_000002_test_migration.php'
        ];

        $this->expectSchemaExists($schema);

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn([
                'test_schema' => [
                    'name' => 'test_schema',
                    'dependencies' => []
                ]
            ]);

        $this->runner->shouldReceive('resetSchema')
            ->with($schema, false)
            ->once()
            ->andReturn($migrations);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema,
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains("Resetting migrations for schema: {$schema}");
    }

    public function testResetWithPretend(): void
    {
        $schema = 'test_schema';
        $migrations = ['2025_02_22_000001_test_migration.php'];

        $this->expectSchemaExists($schema);

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn([
                'test_schema' => [
                    'name' => 'test_schema',
                    'dependencies' => []
                ]
            ]);

        $this->runner->shouldReceive('resetSchema')
            ->with($schema, true)
            ->once()
            ->andReturn($migrations);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema,
            '--pretend' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains("Simulating reset for schema: {$schema}");
    }

    public function testCannotResetSchemaWithDependents(): void
    {
        $schema = 'test_schema';

        $this->expectSchemaExists($schema);

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn([
                'test_schema' => [
                    'name' => 'test_schema',
                    'dependencies' => []
                ],
                'dependent_schema' => [
                    'name' => 'dependent_schema',
                    'dependencies' => ['test_schema']
                ]
            ]);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema,
            '--force' => true
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertOutputContains("Cannot reset schema 'test_schema' - it is required by: dependent_schema");
    }

    public function testRequiresSchemaArgument(): void
    {
        $exitCode = $this->executeCommand($this->command, []);

        $this->assertEquals(1, $exitCode);
        $this->assertOutputContains('Please specify a schema to reset');
    }

    public function testRequiresConfirmationForReset(): void
    {
        $schema = 'test_schema';

        $this->expectSchemaExists($schema);

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn([
                'test_schema' => [
                    'name' => 'test_schema',
                    'dependencies' => []
                ]
            ]);

        // Don't set up any expectations for reset since it should not be called

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertOutputMatches('/Are you sure you want to reset/');
    }

    public function testNothingToReset(): void
    {
        $schema = 'test_schema';

        $this->expectSchemaExists($schema);

        $this->schemaManager->shouldReceive('getSchemas')
            ->once()
            ->andReturn([
                'test_schema' => [
                    'name' => 'test_schema',
                    'dependencies' => []
                ]
            ]);

        $this->runner->shouldReceive('resetSchema')
            ->with($schema, false)
            ->once()
            ->andReturn([]);

        $exitCode = $this->executeCommand($this->command, [
            'schema' => $schema,
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertOutputContains('Nothing to reset');
    }
}
