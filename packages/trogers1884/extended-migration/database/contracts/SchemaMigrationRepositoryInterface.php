<?php

namespace Trogers1884\ExtendedMigration\Database\Contracts;

interface SchemaMigrationRepositoryInterface
{
    /**
     * Determine if the migration repository exists.
     *
     * @param  string  $schema
     * @return bool
     */
    public function repositoryExists(string $schema): bool;

    /**
     * Create the migration repository data store.
     *
     * @param  string  $schema
     * @return void
     */
    public function createRepository(string $schema): void;

    /**
     * Get the list of migrations for a given schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getMigrations(string $schema): array;

    /**
     * Get list of migrations that have already run for a schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getRanMigrations(string $schema): array;

    /**
     * Get list of migrations that haven't run yet for a schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getPendingMigrations(string $schema): array;

    /**
     * Get the last migration batch number for a schema.
     *
     * @param  string  $schema
     * @return int
     */
    public function getLastBatchNumber(string $schema): int;

    /**
     * Log that a migration was run for a schema.
     *
     * @param  string  $schema
     * @param  string  $file
     * @param  int     $batch
     * @return void
     */
    public function log(string $schema, string $file, int $batch): void;

    /**
     * Remove a migration from the log for a schema.
     *
     * @param  string  $schema
     * @param  string  $file
     * @return void
     */
    public function delete(string $schema, string $file): void;

    /**
     * Get the next migration batch number for a schema.
     *
     * @param  string  $schema
     * @return int
     */
    public function getNextBatchNumber(string $schema): int;

    /**
     * Get the migrations for the last batch for a schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getLast(string $schema): array;

    /**
     * Get the complete migration history for a schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getMigrationHistory(string $schema): array;

    /**
     * Delete the migration repository tables.
     *
     * @param  string  $schema
     * @return void
     */
    public function deleteRepository(string $schema): void;

    /**
     * Get the status for all schemas' migrations.
     *
     * @return array
     */
    public function getSchemaStatus(): array;

    /**
     * Set the information source.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function setSource($connection): void;
}
