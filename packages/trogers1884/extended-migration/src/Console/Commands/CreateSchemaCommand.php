<?php

namespace Trogers1884\ExtendedMigration\Console\Commands;

class CreateSchemaCommand extends BaseSchemaCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:create
        {name : The name of the schema to create}
        {path : The path where migrations will be stored}
        {--dependencies=* : Schemas that this schema depends on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new schema for migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $name = $this->argument('name');
            $path = $this->argument('path');
            $dependencies = $this->option('dependencies');

            // Validate schema name isn't taken
            if (!$this->validateSchemaDoesNotExist($name)) {
                return 1;
            }

            // Validate the path
            if (!$this->validateSchemaPath($path)) {
                return 1;
            }

            // Validate dependencies exist
            foreach ($dependencies as $dependency) {
                if (!$this->validateSchemaExists($dependency)) {
                    return 1;
                }
            }

            // Register the schema
            SchemaManager::registerSchema($name, $path, $dependencies);

            // Create the directory structure
            SchemaManager::createPath($name);

            $this->info("Schema '{$name}' has been created successfully.");

            // Display the created schema details
            $schema = SchemaManager::getSchema($name);
            $this->displaySchemaInfo($schema);

            return 0;
        } catch (\Throwable $e) {
            return $this->handleCommandError($e);
        }
    }
}
