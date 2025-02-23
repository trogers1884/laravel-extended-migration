<?php

namespace Trogers1884\ExtendedMigration\Console\Commands;

class SchemaMigrationStatusCommand extends BaseMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:status
                          {schema? : The schema to check}
                          {--pending : Show only schemas with pending migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each schema\'s migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $schema = $this->argument('schema');
            $pendingOnly = $this->option('pending');

            if ($schema) {
                return $this->showSchemaStatus($schema);
            }

            return $this->showAllStatus($pendingOnly);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Show status for a single schema.
     *
     * @param  string  $schema
     * @return int
     */
    protected function showSchemaStatus(string $schema): int
    {
        $this->validateSchemaExists($schema);

        $status = $this->runner->getPendingMigrationStatus();

        if (!isset($status[$schema])) {
            $this->info("No pending migrations for schema: {$schema}");
            return 0;
        }

        $this->table(
            ['Schema', 'Pending', 'Dependencies', 'Ready'],
            $this->formatMigrationStatus([$schema => $status[$schema]])
        );

        if (!empty($status[$schema]['pending'])) {
            $this->info("\nPending Migrations:");
            $this->table(
                ['Migration'],
                collect($status[$schema]['pending'])->map(fn($m) => [$m])->toArray()
            );
        }

        return 0;
    }

    /**
     * Show status for all schemas.
     *
     * @param  bool  $pendingOnly
     * @return int
     */
    protected function showAllStatus(bool $pendingOnly): int
    {
        $status = $this->runner->getPendingMigrationStatus();

        if (empty($status) && !$pendingOnly) {
            $schemas = $this->schemaManager->getSchemas();
            foreach ($schemas as $name => $config) {
                $status[$name] = [
                    'pending' => [],
                    'dependencies' => $config['dependencies'],
                    'can_run' => true,
                ];
            }
        }

        if (empty($status)) {
            $this->info('No pending migrations.');
            return 0;
        }

        $this->table(
            ['Schema', 'Pending', 'Dependencies', 'Ready'],
            $this->formatMigrationStatus($status)
        );

        return 0;
    }
}
