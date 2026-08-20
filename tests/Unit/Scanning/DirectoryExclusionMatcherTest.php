<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Scanning;

/**
 * Decides whether a given absolute path falls under one of a set of
 * excluded directories (either the excluded directory itself or
 * anything nested inside it).
 *
 * All paths are normalized to forward slashes before comparison. On
 * Windows, RecursiveDirectoryIterator joins path segments with
 * backslashes regardless of which separator the starting path used,
 * so comparing raw strings would silently fail to match there.
 */
final readonly class DirectoryExclusionMatcher
{
    /** @var array<int, string> */
    private array $excludedDirectories;

    /**
     * @param  array<int, string>  $excludedDirectories
     */
    public function __construct(array $excludedDirectories)
    {
        $this->excludedDirectories = array_values(array_filter(array_map(
            $this->normalize(...),
            $excludedDirectories,
        )));
    }

    public function matches(string $path): bool
    {
        $normalized = $this->normalize($path);

        foreach ($this->excludedDirectories as $excluded) {
            if ($normalized === $excluded || str_starts_with($normalized.'/', $excluded.'/')) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
