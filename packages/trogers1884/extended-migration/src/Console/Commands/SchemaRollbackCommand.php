<?php

namespace Trogers1884\ExtendedMigration\Console\Commands;

class SchemaRollbackCommand extends BaseMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:rollback
                          {schema? : The schema to rollback}
                          {--pretend : Simulate the rollback operations}
                          {--all : Rollback last batch for all schemas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback migrations for specific schema or all schemas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $schema = $this->argument('schema');
            $pretend = $this->option('pretend');
            $all = $this->option('all');

            if ($all) {
                return $this->rollbackAll($pretend);
            }

            if ($schema === null) {
                $this->error('Please specify a schema or use --all option');
                return 1;
            }

            return $this->rollbackSingle($schema, $pretend);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Rollback a single schema.
     *
     * @param  string  $schema
     * @param  bool  $pretend
     * @return int
     */
    protected function rollbackSingle(string $schema, bool $pretend): int
    {
        $this->validateSchemaExists($schema);

        if ($pretend) {
            $this->info("Simulating rollback for schema: {$schema}");
        } else {
            if (!$this->confirmSchemaOperation('rollback', [$schema])) {
                return 1;
            }
            $this->info("Rolling back migrations for schema: {$schema}");
        }

        $results = $this->runner->rollbackSchemaMigrations($schema, null, $pretend);

        if (empty($results)) {
            $this->info('Nothing to rollback.');
            return 0;
        }

        $this->displayResults([$schema => $results], 'rollback');
        return 0;
    }

    /**
     * Rollback all schemas.
     *
     * @param  bool  $pretend
     * @return int
     */
    protected function rollbackAll(bool $pretend): int
    {
        $schemas = array_keys($this->schemaManager->getSchemas());

        if ($pretend) {
            $this->info('Simulating rollback for all schemas');
        } else {
            if (!$this->confirmSchemaOperation('rollback', $schemas)) {
                return 1;
            }
            $this->info('Rolling back migrations for all schemas');
        }

        $results = $this->runner->rollbackAllSchemas($pretend);

        if (empty($results)) {
            $this->info('Nothing to rollback.');
            return 0;
        }

        $this->displayResults($results, 'rollback');
        return 0;
    }
}
