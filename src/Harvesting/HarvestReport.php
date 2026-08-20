<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Harvesting;

use LaravelLangHarvest\LaravelLangHarvest\Writing\GroupKeyEntry;
use LaravelLangHarvest\LaravelLangHarvest\Writing\GroupMergeResult;
use LaravelLangHarvest\LaravelLangHarvest\Writing\JsonMergeResult;

/**
 * The full result of a harvest run: what was (or would be) added to
 * each lang file, and anything that needs human attention.
 */
final readonly class HarvestReport
{
    /**
     * @param  array<string, GroupMergeResult>  $groupResults  Keyed by group name.
     * @param  array<int, DynamicCallSite>  $dynamicCalls
     * @param  array<int, UnreadableFileNotice>  $unreadableFiles
     */
    public function __construct(
        public int $scannedFileCount,
        public array $groupResults,
        public ?JsonMergeResult $jsonResult,
        public array $dynamicCalls,
        public array $unreadableFiles,
    ) {}

    public function hasPendingChanges(): bool
    {
        foreach ($this->groupResults as $result) {
            if ($result->hasChanges()) {
                return true;
            }
        }

        return $this->jsonResult?->hasChanges() ?? false;
    }

    public function hasConflicts(): bool
    {
        foreach ($this->groupResults as $result) {
            if ($result->conflicts !== []) {
                return true;
            }
        }

        return false;
    }

    public function hasIssues(): bool
    {
        return $this->dynamicCalls !== [] || $this->unreadableFiles !== [] || $this->hasConflicts();
    }

    /**
     * @return array<int, GroupKeyEntry>
     */
    public function addedGroupEntries(): array
    {
        $entries = [];

        foreach ($this->groupResults as $result) {
            foreach ($result->added as $entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @return array<int, GroupKeyEntry>
     */
    public function conflictingGroupEntries(): array
    {
        $entries = [];

        foreach ($this->groupResults as $result) {
            foreach ($result->conflicts as $entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }
}
