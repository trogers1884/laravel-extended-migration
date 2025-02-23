<?php

namespace Trogers1884\ExtendedMigration\Tests\Unit\Console\Commands;

use PHPUnit\Framework\TestCase;
use Mockery;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Trogers1884\ExtendedMigration\Contracts\SchemaManagerInterface;
use Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRunnerInterface;

abstract class BaseMigrationCommandTest extends TestCase
{
    protected $schemaManager;
    protected $runner;
    protected $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaManager = Mockery::mock(SchemaManagerInterface::class);
        $this->runner = Mockery::mock(SchemaMigrationRunnerInterface::class);
        $this->output = new BufferedOutput();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a new command instance.
     *
     * @param  string  $class
     * @return mixed
     */
    protected function createCommand(string $class)
    {
        return new $class($this->schemaManager, $this->runner);
    }

    /**
     * Execute the command.
     *
     * @param  mixed  $command
     * @param  array  $input
     * @return int
     */
    protected function executeCommand($command, array $input): int
    {
        $command->setInput(new ArrayInput($input));
        $command->setOutput($this->output);
        return $command->run();
    }

    /**
     * Get the output from the last command execution.
     *
     * @return string
     */
    protected function getOutput(): string
    {
        return $this->output->fetch();
    }

    /**
     * Set up base schema manager expectations.
     *
     * @param  string  $schema
     * @return void
     */
    protected function expectSchemaExists(string $schema): void
    {
        $this->schemaManager->shouldReceive('hasSchema')
            ->with($schema)
            ->andReturn(true);
    }

    /**
     * Assert command output contains text.
     *
     * @param  string  $text
     * @return void
     */
    protected function assertOutputContains(string $text): void
    {
        $this->assertStringContainsString($text, $this->getOutput());
    }

    /**
     * Assert command output matches pattern.
     *
     * @param  string  $pattern
     * @return void
     */
    protected function assertOutputMatches(string $pattern): void
    {
        $this->assertMatchesRegularExpression($pattern, $this->getOutput());
    }
}
