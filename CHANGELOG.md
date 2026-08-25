# Release Notes

## [Unreleased](https://github.com/tunahan-genc/laravel-lang-harvest/compare/v0.1.0...HEAD)

## [v0.1.0](https://github.com/tunahan-genc/laravel-lang-harvest/releases/tag/v0.1.0) - 2026-08-20

Laravel Lang Harvest v0.1.0 — first release. Scans your Laravel + Blade codebase for `__()`, `trans()`, `trans_choice()`, `@lang()`, `@choice()`, and `Lang::get()` calls, and adds any missing keys to your lang files automatically.

### Highlights

- `php artisan lang:harvest` — scans configured paths, classifies each static key into `lang/{locale}/{group}.php` (dotted keys) or `lang/{locale}.json` (plain text)
- Static analysis via `nikic/php-parser` — never executes your code, never guesses at dynamic values
- Ternary expressions with static branches (e.g. `$cond ? 'Edit Post' : 'Create Post'`) are fully harvested; anything genuinely dynamic is skipped and reported instead
- Existing translations are never overwritten — only missing keys are added
- `--dry-run` to preview changes, `--check` to fail CI when something's missing or needs review
- Configurable locale, scan paths, excluded directories, and placeholder format

### What's Changed

- Add core extraction logic and utility classes by @Tunahangncc in https://github.com/Tunahangncc/laravel-lang-harvest/pull/1
- Add translation harvesting logic by @Tunahangncc in https://github.com/Tunahangncc/laravel-lang-harvest/pull/2

**Full Changelog**: https://github.com/Tunahangncc/laravel-lang-harvest/commits/v0.1.0
