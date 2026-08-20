<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Writing;

/**
 * The result of merging a set of harvested text strings into an
 * existing lang/{locale}.json identity map.
 */
final readonly class JsonMergeResult
{
    /**
     * @param  array<string, string>  $data  The merged map, ready to be written.
     * @param  array<int, string>  $added
     */
    public function __construct(
        public array $data,
        public array $added,
    ) {}

    public function hasChanges(): bool
    {
        return $this->added !== [];
    }
}
