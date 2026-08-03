<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Writing;

enum InsertOutcome
{
    /** The key was missing and has been added with its placeholder value. */
    case Added;

    /** The key already existed; its value was left untouched. */
    case AlreadyExists;

    /**
     * A segment along the key's path already exists as a non-array
     * value, so the key cannot be inserted without overwriting it.
     */
    case Conflict;
}
