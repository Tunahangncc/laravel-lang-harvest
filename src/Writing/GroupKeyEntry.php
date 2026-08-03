<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Writing;

/**
 * A single dotted key waiting to be inserted into a lang group file,
 * along with the nested path it needs and the placeholder value to
 * write if it is missing.
 */
final readonly class GroupKeyEntry
{
    /**
     * @param  array<int, string>  $segments  Nested path inside the group file.
     */
    public function __construct(
        public string $rawKey,
        public array $segments,
        public string $placeholder,
    ) {}
}
