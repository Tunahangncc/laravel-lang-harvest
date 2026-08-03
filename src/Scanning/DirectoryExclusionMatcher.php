<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Scanning;

/**
 * Decides whether a given absolute path falls under one of a set of
 * excluded directories (either the excluded directory itself or
 * anything nested inside it).
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
        $this->excludedDirectories = array_map(
            static fn (string $path): string => rtrim($path, '/\\'),
            $excludedDirectories,
        );
    }

    public function matches(string $path): bool
    {
        $normalized = rtrim($path, '/\\');

        foreach ($this->excludedDirectories as $excluded) {
            if ($excluded === '') {
                continue;
            }

            if ($normalized === $excluded || str_starts_with($normalized.'/', $excluded.'/')) {
                return true;
            }
        }

        return false;
    }
}
