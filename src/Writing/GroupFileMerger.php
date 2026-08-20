<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Writing;

/**
 * Merges harvested group keys into an existing nested lang array,
 * without ever overwriting an existing value.
 */
final class GroupFileMerger
{
    /**
     * @param  array<array-key, mixed>  $existing
     * @param  array<int, GroupKeyEntry>  $entries
     */
    public function merge(array $existing, array $entries): GroupMergeResult
    {
        $data = $existing;
        $added = [];
        $conflicts = [];

        foreach ($entries as $entry) {
            [$data, $outcome] = $this->insert($data, $entry->segments, $entry->placeholder);

            match ($outcome) {
                InsertOutcome::Added => $added[] = $entry,
                InsertOutcome::AlreadyExists => null,
                InsertOutcome::Conflict => $conflicts[] = $entry,
            };
        }

        return new GroupMergeResult($data, $added, $conflicts);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @param  array<int, string>  $segments
     * @return array{0: array<array-key, mixed>, 1: InsertOutcome}
     */
    private function insert(array $data, array $segments, string $placeholder): array
    {
        $segment = array_shift($segments);

        if ($segments === []) {
            if (array_key_exists($segment, $data)) {
                return [$data, InsertOutcome::AlreadyExists];
            }

            $data[$segment] = $placeholder;

            return [$data, InsertOutcome::Added];
        }

        $child = $data[$segment] ?? [];

        if (! is_array($child)) {
            return [$data, InsertOutcome::Conflict];
        }

        [$updatedChild, $outcome] = $this->insert($child, $segments, $placeholder);

        $data[$segment] = $updatedChild;

        return [$data, $outcome];
    }
}
