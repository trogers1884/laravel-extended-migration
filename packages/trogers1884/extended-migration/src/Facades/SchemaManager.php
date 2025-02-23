<?php

namespace Trogers1884\ExtendedMigration\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool registerSchema(string $name, string $path, array $dependencies = [])
 * @method static bool hasSchema(string $name)
 * @method static array|null getSchema(string $name)
 * @method static array getSchemas()
 * @method static array getDependencies(string $name)
 * @method static bool validateSchema(string $name)
 *
 * @see \Trogers1884\ExtendedMigration\Support\SchemaManager
 */
class SchemaManager extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'schema-manager';
    }
}
