<?php

namespace Trogers1884\ExtendedMigration\Contracts;

interface SchemaManagerInterface
{
    /**
     * Register a new schema with the manager.
     *
     * @param string $name The name of the schema
     * @param string $path The base path for schema migrations
     * @param array $dependencies Array of schema names this schema depends on
     * @return bool
     */
    public function registerSchema(string $name, string $path, array $dependencies = []): bool;

    /**
     * Check if a schema is registered.
     *
     * @param string $name
     * @return bool
     */
    public function hasSchema(string $name): bool;

    /**
     * Get schema configuration if it exists.
     *
     * @param string $name
     * @return array|null
     */
    public function getSchema(string $name): ?array;

    /**
     * Get all registered schemas.
     *
     * @return array
     */
    public function getSchemas(): array;

    /**
     * Get schema dependencies.
     *
     * @param string $name
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getDependencies(string $name): array;

    /**
     * Validate schema configuration.
     *
     * @param string $name
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateSchema(string $name): bool;

    /**
     * Set the migration path for a schema.
     *
     * @param string $name The name of the schema
     * @param string $path The path to set
     * @return bool
     * @throws \InvalidArgumentException If schema doesn't exist
     */
    public function setPath(string $name, string $path): bool;

    /**
     * Get the migration path for a schema.
     *
     * @param string $name The name of the schema
     * @return string
     * @throws \InvalidArgumentException If schema doesn't exist
     */
    public function getPath(string $name): string;

    /**
     * Check if a schema's migration path exists.
     *
     * @param string $name The name of the schema
     * @return bool
     * @throws \InvalidArgumentException If schema doesn't exist
     */
    public function pathExists(string $name): bool;

    /**
     * Create the migration path for a schema if it doesn't exist.
     *
     * @param string $name The name of the schema
     * @return bool
     * @throws \InvalidArgumentException If schema doesn't exist
     */
    public function createPath(string $name): bool;

    /**
     * Validate a path for use with schemas.
     *
     * @param string $path The path to validate
     * @return bool
     * @throws \InvalidArgumentException If path is invalid
     */
    public function validatePath(string $path): bool;

    /**
     * Add a dependency to a schema.
     *
     * @param string $name Schema name
     * @param string $dependency Schema name to add as dependency
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function addDependency(string $name, string $dependency): bool;

    /**
     * Remove a dependency from a schema.
     *
     * @param string $name Schema name
     * @param string $dependency Schema name to remove from dependencies
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function removeDependency(string $name, string $dependency): bool;

    /**
     * Validate all dependencies for a schema.
     *
     * @param string $name Schema name
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateDependencies(string $name): bool;

    /**
     * Get the complete dependency graph for all schemas.
     *
     * @return array<string, array<string>>
     */
    public function getDependencyGraph(): array;

    /**
     * Check for circular dependencies in the schema.
     *
     * @param string $name Schema name to check
     * @return bool True if no circular dependencies are found
     * @throws \InvalidArgumentException If circular dependencies are detected
     */
    public function checkCircularDependencies(string $name): bool;


}
