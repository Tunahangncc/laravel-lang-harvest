<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Scanning;

use RecursiveDirectoryIterator;
use RecursiveFilterIterator;
use SplFileInfo;

/**
 * Wraps a RecursiveDirectoryIterator so that directories matched by a
 * DirectoryExclusionMatcher are never descended into (their contents
 * are simply never visited, rather than being visited and filtered out
 * afterwards).
 *
 * @internal Used by FileScanner; not part of the public API.
 */
final class ExcludingRecursiveFilterIterator extends RecursiveFilterIterator
{
    public function __construct(
        private readonly RecursiveDirectoryIterator $directoryIterator,
        private readonly DirectoryExclusionMatcher $exclusionMatcher,
    ) {
        parent::__construct($directoryIterator);
    }

    public function accept(): bool
    {
        $current = $this->current();

        if (! $current instanceof SplFileInfo || ! $current->isDir()) {
            return true;
        }

        return ! $this->exclusionMatcher->matches($current->getPathname());
    }

    public function getChildren(): self
    {
        return new self($this->directoryIterator->getChildren(), $this->exclusionMatcher);
    }
}
