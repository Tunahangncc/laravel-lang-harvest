<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Scanning;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Recursively finds .php and .blade.php files under a set of configured
 * paths, skipping any directory that falls under one of the configured
 * excluded directories entirely (it is never descended into).
 *
 * Every returned path is normalized to forward slashes, regardless of
 * platform, so output is consistent between Windows and Unix.
 */
final class FileScanner
{
    /**
     * @param  array<int, string>  $paths  Absolute file or directory paths to scan.
     * @param  array<int, string>  $excludedDirectories  Absolute directory paths to skip entirely.
     * @return array<int, string> Absolute file paths, deduplicated and sorted.
     */
    public function scan(array $paths, array $excludedDirectories = []): array
    {
        $exclusionMatcher = new DirectoryExclusionMatcher($excludedDirectories);

        $files = [];

        foreach ($paths as $path) {
            foreach ($this->scanPath($path, $exclusionMatcher) as $file) {
                $files[$file] = true;
            }
        }

        $files = array_keys($files);
        sort($files);

        return $files;
    }

    /**
     * @return iterable<string>
     */
    private function scanPath(string $path, DirectoryExclusionMatcher $exclusionMatcher): iterable
    {
        if (is_file($path)) {
            if ($this->hasPhpExtension($path)) {
                yield $this->normalize($path);
            }

            return;
        }

        if (! is_dir($path) || $exclusionMatcher->matches($path)) {
            return;
        }

        $directoryIterator = new RecursiveDirectoryIterator(
            $path,
            FilesystemIterator::SKIP_DOTS,
        );

        $filtered = new ExcludingRecursiveFilterIterator($directoryIterator, $exclusionMatcher);

        $iterator = new RecursiveIteratorIterator($filtered);

        foreach ($iterator as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if ($fileInfo->isFile() && $this->hasPhpExtension($fileInfo->getPathname())) {
                yield $this->normalize($fileInfo->getPathname());
            }
        }
    }

    private function hasPhpExtension(string $path): bool
    {
        return str_ends_with($path, '.php');
    }

    private function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
