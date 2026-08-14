# Release Notes

## [Unreleased](https://github.com/tunahan-genc/laravel-lang-harvest/compare/v0.1.0...1.x)

### Added

- `lang:harvest` Artisan command that scans configured PHP and Blade paths for `__()`, `trans()`, `trans_choice()`, `@lang()`, `@choice()`, and `Lang::get()` calls
- Static string literal detection via `nikic/php-parser`; dynamic arguments are skipped and reported, never guessed at
- Automatic classification of harvested keys into `lang/{locale}/{group}.php` (dotted/group keys) or `lang/{locale}.json` (plain text, identity-mapped)
- Existing translation values are never overwritten; only missing keys are added
- `--dry-run` and `--check` command options
- Configurable locale, scan paths, excluded directories, and placeholder format

## [v0.1.0](https://github.com/tunahan-genc/laravel-lang-harvest/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
