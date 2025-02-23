<?php

namespace Trogers1884\ExtendedMigration\Console\Commands;

class ListSchemaCommand extends BaseSchemaCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:list {--detail : Show detailed information for each schema}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered schemas and their dependencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $schemas = SchemaManager::getSchemas();

            if (empty($schemas)) {
                $this->info('No schemas are currently registered.');
                return 0;
            }

            if ($this->option('detail')) {
                $this->displayDetailedList($schemas);
            } else {
                $this->displaySimpleList($schemas);
            }

            return 0;
        } catch (\Throwable $e) {
            return $this->handleCommandError($e);
        }
    }

    /**
     * Display a simple list of schemas.
     */
    private function displaySimpleList(array $schemas): void
    {
        $rows = collect($schemas)->map(function ($schema) {
            return [
                $schema['name'],
                empty($schema['dependencies']) ? 'None' : implode(', ', $schema['dependencies'])
            ];
        })->toArray();

        $this->table(['Schema', 'Dependencies'], $rows);
    }

    /**
     * Display detailed information for each schema.
     */
    private function displayDetailedList(array $schemas): void
    {
        foreach ($schemas as $schema) {
            $this->line("\n<info>Schema: {$schema['name']}</info>");
            $this->displaySchemaInfo($schema);
        }
    }
}
