<?php

namespace Trogers1884\ExtendedMigration\Console\Commands;

class SchemaResetCommand extends BaseMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:reset
                          {schema? : The schema to reset}
                          {--pretend : Simulate the reset operations}
                          {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all migrations for a specific schema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $schema = $this->argument('schema');
            $pretend = $this->option('pretend');
            $force = $this->option('force');

            if ($schema === null) {
                $this->error('Please specify a schema to reset');
                return 1;
            }

            return $this->resetSchema($schema, $pretend, $force);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Reset a schema's migrations.
     *
     * @param  string  $schema
     * @param  bool  $pretend
     * @param  bool  $force
     * @return int
     */
    protected function resetSchema(string $schema, bool $pretend, bool $force): int
    {
        $this->validateSchemaExists($schema);

        // Check for dependent schemas
        $dependents = $this->findDependentSchemas($schema);
        if (!empty($dependents)) {
            $this->error("Cannot reset schema '{$schema}' - it is required by: " . implode(', ', $dependents));
            return 1;
        }

        if ($pretend) {
            $this->info("Simulating reset for schema: {$schema}");
        } else {
            if (!$force && !$this->confirmSchemaOperation('reset', [$schema])) {
                return 1;
            }
            $this->info("Resetting migrations for schema: {$schema}");
        }

        $results = $this->runner->resetSchema($schema, $pretend);

        if (empty($results)) {
            $this->info('Nothing to reset.');
            return 0;
        }

        $this->displayResults([$schema => $results], 'reset');
        return 0;
    }

    /**
     * Find schemas that depend on the given schema.
     *
     * @param  string  $schema
     * @return array
     */
    protected function findDependentSchemas(string $schema): array
    {
        $dependents = [];
        $schemas = $this->schemaManager->getSchemas();

        foreach ($schemas as $name => $config) {
            if (in_array($schema, $config['dependencies'])) {
                $dependents[] = $name;
            }
        }

        return $dependents;
    }
}
