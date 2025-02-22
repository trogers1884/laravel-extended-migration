<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Schema Configuration
    |--------------------------------------------------------------------------
    |
    | Define your PostgreSQL schemas and their respective migration paths.
    | The 'default' schema is required and maps to Laravel's default migrations.
    |
    */
    'schemas' => [
        'default' => [
            'path' => database_path('migrations'),
            'dependencies' => [],
        ],
        // Add additional schemas as needed:
        // 'analytics' => [
        //     'path' => database_path('migrations/analytics'),
        //     'dependencies' => ['default'],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Mode
    |--------------------------------------------------------------------------
    |
    | Configure how transactions are handled during migrations.
    | Options: 'per_schema', 'global', 'none'
    |
    */
    'transaction_mode' => 'per_schema',

    /*
    |--------------------------------------------------------------------------
    | Migration Table
    |--------------------------------------------------------------------------
    |
    | Configure the migration table name for each schema.
    | {schema} will be replaced with the actual schema name.
    |
    */
    'migration_table' => '{schema}_migrations',

    /*
    |--------------------------------------------------------------------------
    | Batch Processing
    |--------------------------------------------------------------------------
    |
    | Configure how migration batches are processed.
    |
    */
    'batch_processing' => [
        // Maximum number of migrations to run in parallel (when possible)
        'max_parallel' => 1,

        // Whether to stop all migrations if one fails
        'stop_on_failure' => true,
    ],
];
