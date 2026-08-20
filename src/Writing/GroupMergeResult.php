<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Writing;

/**
 * The result of merging a set of GroupKeyEntry items into an existing
 * nested lang array.
 */
final readonly class GroupMergeResult
{
    /**
     * @param  array<array-key, mixed>  $data  The merged array, ready to be rendered and written.
     * @param  array<int, GroupKeyEntry>  $added
     * @param  array<int, GroupKeyEntry>  $conflicts
     */
    public function __construct(
        public array $data,
        public array $added,
        public array $conflicts,
    ) {}

    public function hasChanges(): bool
    {
        return $this->added !== [];
    }
}
