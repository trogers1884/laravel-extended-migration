<?php

namespace Trogers1884\ExtendedMigration\Console\Commands;

use Illuminate\Console\Command;
use Trogers1884\ExtendedMigration\Facades\SchemaManager;
use InvalidArgumentException;

abstract class BaseSchemaCommand extends Command
{
    /**
     * Validate that a schema exists.
     *
     * @param string $name
     * @return bool
     */
    protected function validateSchemaExists(string $name): bool
    {
        if (!SchemaManager::hasSchema($name)) {
            $this->error("Schema '{$name}' does not exist.");
            return false;
        }

        return true;
    }

    /**
     * Validate that a schema does not exist.
     *
     * @param string $name
     * @return bool
     */
    protected function validateSchemaDoesNotExist(string $name): bool
    {
        if (SchemaManager::hasSchema($name)) {
            $this->error("Schema '{$name}' already exists.");
            return false;
        }

        return true;
    }

    /**
     * Validate schema dependencies.
     *
     * @param string $name
     * @return bool
     */
    protected function validateSchemaDependencies(string $name): bool
    {
        try {
            return SchemaManager::validateDependencies($name);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return false;
        }
    }

    /**
     * Validate schema path.
     *
     * @param string $path
     * @return bool
     */
    protected function validateSchemaPath(string $path): bool
    {
        try {
            return SchemaManager::validatePath($path);
        } catch (InvalidArgumentException $e) {
            $this->error("Invalid path: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Format schema information for display.
     *
     * @param array $schema
     * @return array
     */
    protected function formatSchemaInfo(array $schema): array
    {
        return [
            'Name' => $schema['name'],
            'Path' => $schema['path'],
            'Dependencies' => empty($schema['dependencies'])
                ? 'None'
                : implode(', ', $schema['dependencies']),
            'Path Exists' => SchemaManager::pathExists($schema['name']) ? 'Yes' : 'No'
        ];
    }

    /**
     * Display schema details in the console.
     *
     * @param array $schema
     * @return void
     */
    protected function displaySchemaInfo(array $schema): void
    {
        $info = $this->formatSchemaInfo($schema);

        $this->table(
            ['Property', 'Value'],
            collect($info)->map(fn($value, $key) => [$key, $value])->toArray()
        );
    }

    /**
     * Handle common command errors.
     *
     * @param \Throwable $e
     * @return int
     */
    protected function handleCommandError(\Throwable $e): int
    {
        $this->error($e->getMessage());

        // Provide more context for specific error types
        if ($e instanceof InvalidArgumentException) {
            $this->line('Please check your input and try again.');
        }

        return 1; // Command failed
    }

    /**
     * Confirm destructive actions with the user.
     *
     * @param string $action Description of the action
     * @param string $item Item being affected
     * @return bool
     */
    protected function confirmDestructiveAction(string $action, string $item): bool
    {
        return $this->confirm(
            "Are you sure you want to {$action} '{$item}'? This action cannot be undone.",
            false
        );
    }
}
