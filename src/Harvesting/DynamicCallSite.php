<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Harvesting;

/**
 * A translation call whose first argument could not be resolved to a
 * static string, reported so a human can review it manually.
 */
final readonly class DynamicCallSite
{
    public function __construct(
        public string $file,
        public int $line,
        public string $callee,
    ) {}
}
