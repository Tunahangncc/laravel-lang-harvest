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

<!-- Add a basic usage example here. -->

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
