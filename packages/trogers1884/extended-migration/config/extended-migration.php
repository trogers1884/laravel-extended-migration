<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Schema Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the default settings for schema management.
    |
    */

    'schemas' => [
        /*
        |--------------------------------------------------------------------------
        | Default Schema
        |--------------------------------------------------------------------------
        |
        | The default schema to use when none is specified.
        |
        */
        'default' => 'public',

        /*
        |--------------------------------------------------------------------------
        | Schema Paths
        |--------------------------------------------------------------------------
        |
        | Define the base paths for each schema's migrations.
        | Example:
        |
        | 'paths' => [
        |     'public' => database_path('migrations/public'),
        |     'tenant' => database_path('migrations/tenant'),
        | ],
        |
        */
        'paths' => [
            'public' => database_path('migrations'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Schema Dependencies
        |--------------------------------------------------------------------------
        |
        | Define dependencies between schemas. Migrations in dependent schemas
        | will only run after their dependencies are satisfied.
        |
        | Example:
        |
        | 'dependencies' => [
        |     'tenant' => ['public'],
        |     'reporting' => ['public', 'tenant'],
        | ],
        |
        */
        'dependencies' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    |
    | Configure how transactions are handled across schemas.
    |
    */
    'transactions' => [
        // Whether to wrap cross-schema migrations in a transaction
        'enabled' => true,

        // Maximum number of savepoints to create
        'max_savepoints' => 5,
    ],
];

