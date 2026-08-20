<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Harvesting;

/**
 * A source or lang file that could not be read/parsed/safely merged,
 * reported so a human can review it manually.
 */
final readonly class UnreadableFileNotice
{
    public function __construct(
        public string $path,
        public string $reason,
    ) {}
}
