<?php

namespace Trogers1884\ExtendedMigration\Console\Commands;

class ValidateSchemaCommand extends BaseSchemaCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:validate
        {name? : The name of the schema to validate. If omitted, validates all schemas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate schema configuration and dependencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $name = $this->argument('name');

            if ($name) {
                return $this->validateSingleSchema($name);
            }

            return $this->validateAllSchemas();
        } catch (\Throwable $e) {
            return $this->handleCommandError($e);
        }
    }

    /**
     * Validate a single schema.
     */
    private function validateSingleSchema(string $name): int
    {
        if (!$this->validateSchemaExists($name)) {
            return 1;
        }

        $this->line("\n<info>Validating schema: {$name}</info>");

        try {
            // Validate the schema configuration
            SchemaManager::validateSchema($name);
            $this->info('✓ Basic configuration is valid');

            // Validate schema dependencies
            SchemaManager::validateDependencies($name);
            $this->info('✓ Dependencies are valid');

            // Check for circular dependencies
            SchemaManager::checkCircularDependencies($name);
            $this->info('✓ No circular dependencies detected');

            // Validate path exists
            if (SchemaManager::pathExists($name)) {
                $this->info('✓ Migration path exists and is accessible');
            } else {
                $this->warn('! Migration path does not exist');
            }

            $schema = SchemaManager::getSchema($name);
            $this->displaySchemaInfo($schema);

            return 0;
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Validate all registered schemas.
     */
    private function validateAllSchemas(): int
    {
        $schemas = SchemaManager::getSchemas();

        if (empty($schemas)) {
            $this->info('No schemas are currently registered.');
            return 0;
        }

        $hasErrors = false;

        foreach ($schemas as $schema) {
            $this->line("\n<info>Validating schema: {$schema['name']}</info>");

            try {
                SchemaManager::validateSchema($schema['name']);
                SchemaManager::validateDependencies($schema['name']);
                SchemaManager::checkCircularDependencies($schema['name']);

                $this->info("✓ Schema '{$schema['name']}' is valid");
            } catch (\InvalidArgumentException $e) {
                $this->error($e->getMessage());
                $hasErrors = true;
            }
        }

        if ($hasErrors) {
            $this->error("\nValidation completed with errors.");
            return 1;
        }

        $this->info("\nAll schemas validated successfully.");
        return 0;
    }
}
