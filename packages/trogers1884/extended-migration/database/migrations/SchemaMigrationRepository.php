<?php

namespace Trogers1884\ExtendedMigration\Database\Migrations;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Trogers1884\ExtendedMigration\Database\Contracts\SchemaMigrationRepositoryInterface;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Support\Collection;

class SchemaMigrationRepository extends DatabaseMigrationRepository implements SchemaMigrationRepositoryInterface
{
    /**
     * Create a new database migration repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $table
     * @return void
     */
    public function __construct(Resolver $resolver, $table)
    {
        parent::__construct($resolver, $table);
    }

    /**
     * Get the migration table name for a specific schema.
     *
     * @param  string  $schema
     * @return string
     */
    protected function getTableName(string $schema): string
    {
        return $schema . '.' . $this->table;
    }

    /**
     * Determine if the migration repository exists.
     *
     * @param  string  $schema
     * @return bool
     */
    public function repositoryExists(string $schema): bool
    {
        $schema = $this->resolveConnection()->getSchemaBuilder();

        return $schema->hasTable($this->getTableName($schema));
    }

    /**
     * Create the migration repository data store.
     *
     * @param  string  $schema
     * @return void
     */
    public function createRepository(string $schema): void
    {
        $schema = $this->resolveConnection()->getSchemaBuilder();

        $schema->create($this->getTableName($schema), function ($table) {
            // Add id as primary key
            $table->increments('id');
            // Migration name with unique constraint
            $table->string('migration')->unique();
            // Batch number for rollback support
            $table->integer('batch');
            // Add execution time tracking
            $table->timestamp('executed_at')->useCurrent();
        });
    }

    /**
     * Get the list of migrations for a given schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getMigrations(string $schema): array
    {
        return $this->table()
            ->where('migration', 'like', $schema . '/%')
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get list of migrations that have already run for a schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getRanMigrations(string $schema): array
    {
        return $this->table()
            ->where('migration', 'like', $schema . '/%')
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('migration')
            ->toArray();
    }

    /**
     * Get list of migrations that haven't run yet for a schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getPendingMigrations(string $schema): array
    {
        $files = Collection::make(
            glob(database_path("migrations/{$schema}/*.php"))
        )->map(function ($file) use ($schema) {
            return $schema . '/' . basename($file);
        })->toArray();

        return array_diff($files, $this->getRanMigrations($schema));
    }

    /**
     * Get the last migration batch number for a schema.
     *
     * @param  string  $schema
     * @return int
     */
    public function getLastBatchNumber(string $schema): int
    {
        return $this->table()
            ->where('migration', 'like', $schema . '/%')
            ->max('batch') ?? 0;
    }

    /**
     * Log that a migration was run for a schema.
     *
     * @param  string  $schema
     * @param  string  $file
     * @param  int     $batch
     * @return void
     */
    public function log(string $schema, string $file, int $batch): void
    {
        $record = ['migration' => $schema . '/' . $file, 'batch' => $batch];

        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log for a schema.
     *
     * @param  string  $schema
     * @param  string  $file
     * @return void
     */
    public function delete(string $schema, string $file): void
    {
        $this->table()->where('migration', $schema . '/' . $file)->delete();
    }

    /**
     * Get the next migration batch number for a schema.
     *
     * @param  string  $schema
     * @return int
     */
    public function getNextBatchNumber(string $schema): int
    {
        return $this->getLastBatchNumber($schema) + 1;
    }

    /**
     * Get the migrations for the last batch for a schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getLast(string $schema): array
    {
        $batch = $this->getLastBatchNumber($schema);

        return $this->table()
            ->where('migration', 'like', $schema . '/%')
            ->where('batch', $batch)
            ->orderBy('migration', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get the complete migration history for a schema.
     *
     * @param  string  $schema
     * @return array
     */
    public function getMigrationHistory(string $schema): array
    {
        return $this->table()
            ->where('migration', 'like', $schema . '/%')
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Delete the migration repository tables.
     *
     * @param  string  $schema
     * @return void
     */
    public function deleteRepository(string $schema): void
    {
        $this->resolveConnection()->getSchemaBuilder()
            ->dropIfExists($this->getTableName($schema));
    }

    /**
     * Get the status for all schemas' migrations.
     *
     * @return array
     */
    public function getSchemaStatus(): array
    {
        $migrations = $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->get();

        $status = [];
        foreach ($migrations as $migration) {
            [$schema] = explode('/', $migration->migration, 2);
            if (!isset($status[$schema])) {
                $status[$schema] = [
                    'total' => 0,
                    'last_batch' => 0,
                    'last_migration' => null,
                    'last_executed' => null,
                ];
            }

            $status[$schema]['total']++;
            $status[$schema]['last_batch'] = max(
                $status[$schema]['last_batch'],
                $migration->batch
            );
            $status[$schema]['last_migration'] = $migration->migration;
            $status[$schema]['last_executed'] = $migration->executed_at;
        }

        return $status;
    }
}
