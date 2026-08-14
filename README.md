<div align="center">
    <h1>Laravel Lang Harvest</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/tunahan-genc/laravel-lang-harvest"><img src="https://img.shields.io/packagist/v/tunahan-genc/laravel-lang-harvest.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/tunahan-genc/laravel-lang-harvest"><img src="https://img.shields.io/packagist/php-v/tunahan-genc/laravel-lang-harvest.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/tunahan-genc/laravel-lang-harvest"><img src="https://badge.laravel.cloud/badge/tunahan-genc/laravel-lang-harvest?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/tunahan-genc/laravel-lang-harvest/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/tunahan-genc/laravel-lang-harvest/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/tunahan-genc/laravel-lang-harvest"><img src="https://img.shields.io/packagist/dt/tunahan-genc/laravel-lang-harvest.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Scan Laravel projects for static translation strings and sync them to JSON and PHP language files.

## Installation

You can install the package via Composer:

```bash
composer require tunahan-genc/laravel-lang-harvest
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="laravel-lang-harvest"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="laravel-lang-harvest-config"
```

## Usage

After publishing the config, adjust `config/laravel-lang-harvest.php` to match your project (scan paths, excluded directories, locale, placeholder format), then run:

```bash
php artisan lang:harvest
```

This scans the configured paths for `__()`, `trans()`, `trans_choice()`, `@lang()`, `@choice()`, and `Lang::get()` calls, and adds any missing keys to your `lang/{locale}/{group}.php` and `lang/{locale}.json` files. **Existing values are never overwritten** — only missing keys are added.

### Dotted keys vs. plain text

- `__('arac.lastik-gecmisi')` → written to `lang/en/arac.php` as `'lastik-gecmisi' => '[arac.lastik-gecmisi]'`
- `__('profile.edit.title')` → written to `lang/en/profile.php` as a nested array: `'edit' => ['title' => '[profile.edit.title]']`
- `__('Welcome to our site!')` → written to `lang/en.json` as `"Welcome to our site!": "Welcome to our site!"`

Only strings matching `word(.word)+` (letters, numbers, underscores, and hyphens joined by dots) are treated as dotted/group keys; everything else is treated as plain text for the JSON file.

### Options

```bash
# Preview what would be added, without writing any files
php artisan lang:harvest --dry-run

# Same as --dry-run, but exits with a non-zero status if anything is
# missing or needs manual review (useful in CI)
php artisan lang:harvest --check
```

Dynamic calls (e.g. `trans($key)`, `__("prefix.{$suffix}")`) are always skipped and reported separately — they are never guessed at.

A ternary with two static branches (e.g. `__($isEdit ? 'Edit Post' : 'Create New Post')`) is a special case: since every possible outcome is known ahead of time, both branches are harvested. If any branch of a ternary is itself dynamic, the whole call is skipped and reported instead — no partial harvesting.

### Configuration

```php
return [
    // Falls back to config('app.locale') when null
    'locale' => env('LANG_HARVEST_LOCALE'),

    // Relative to the application's base path (absolute paths also work)
    'paths' => ['app', 'resources/views'],

    'exclude' => ['vendor', 'node_modules', 'storage', 'bootstrap/cache', 'tests'],

    // %s is replaced with the full dotted key
    'placeholder' => '[%s]',
];
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Lang Harvest! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [tunahan.genc](https://github.com/tunahan-genc)
- [All Contributors](../../contributors)

## License

Laravel Lang Harvest is open-sourced software licensed under the [MIT license](LICENSE.md).
