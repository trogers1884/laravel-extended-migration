# Laravel Extended Migration

A Laravel package that extends the default migration system to support complex PostgreSQL database architectures with multiple schemas and foreign data wrappers.

## Features

- Multiple migration paths for different PostgreSQL schemas
- Independent tracking of migrations per schema
- Managed dependencies between schemas
- Transaction management across schema boundaries
- Full compatibility with Laravel's existing migration system

## Requirements

- PHP 8.2 or higher
- Laravel 11.x
- PostgreSQL 17.x
- Composer

## Installation

You can install the package via composer:

```bash
composer require trogers1884/laravel-extended-migration
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Trogers1884\ExtendedMigration\ExtendedMigrationServiceProvider"
```

## Usage

[Documentation coming soon]

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [your email] instead of using the issue tracker.

## Credits

- [Your Name](https://github.com/trogers1884)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
