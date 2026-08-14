<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Config;

/**
 * Reads the package's config array into typed, ready-to-use values:
 * relative paths resolved against the application's base path, and a
 * placeholder formatter for newly harvested group keys.
 *
 * Takes the base path and fallback locale as plain constructor
 * arguments (rather than calling Laravel's base_path()/app() helpers
 * directly) so it can be unit tested without booting the framework.
 */
final readonly class HarvestConfig
{
    /**
     * @param  array<int, string>  $paths  Relative or absolute scan paths.
     * @param  array<int, string>  $exclude  Relative or absolute excluded directories.
     */
    public function __construct(
        private string $basePath,
        private string $locale,
        private array $paths,
        private array $exclude,
        private string $placeholderFormat,
    ) {}

    /**
     * @param  array<string, mixed>  $config  The package's config array (config('laravel-lang-harvest')).
     */
    public static function fromArray(string $basePath, array $config, string $fallbackLocale): self
    {
        /** @var string|null $locale */
        $locale = $config['locale'] ?? null;

        /** @var array<int, string> $paths */
        $paths = $config['paths'] ?? ['app', 'resources/views'];

        /** @var array<int, string> $exclude */
        $exclude = $config['exclude'] ?? [];

        /** @var string $placeholderFormat */
        $placeholderFormat = $config['placeholder'] ?? '[%s]';

        return new self(
            basePath: $basePath,
            locale: ($locale === null || $locale === '') ? $fallbackLocale : $locale,
            paths: $paths,
            exclude: $exclude,
            placeholderFormat: $placeholderFormat,
        );
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * @return array<int, string> Absolute paths.
     */
    public function scanPaths(): array
    {
        return array_map($this->toAbsolutePath(...), $this->paths);
    }

    /**
     * @return array<int, string> Absolute paths.
     */
    public function excludedDirectories(): array
    {
        return array_map($this->toAbsolutePath(...), $this->exclude);
    }

    public function placeholder(string $key): string
    {
        return sprintf($this->placeholderFormat, $key);
    }

    private function toAbsolutePath(string $path): string
    {
        if ($this->isAbsolute($path)) {
            return rtrim($path, '/\\');
        }

        return rtrim($this->basePath, '/\\').'/'.ltrim($path, '/\\');
    }

    private function isAbsolute(string $path): bool
    {
        if (str_starts_with($path, '/')) {
            return true;
        }

        return preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1;
    }
}
