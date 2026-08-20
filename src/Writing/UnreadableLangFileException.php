<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Writing;

use RuntimeException;
use Throwable;

final class UnreadableLangFileException extends RuntimeException
{
    public static function unreadable(string $path): self
    {
        return new self("Unable to read lang file \"{$path}\".");
    }

    public static function invalidSyntax(string $path, Throwable $previous): self
    {
        return new self(
            "Lang file \"{$path}\" contains invalid PHP: {$previous->getMessage()}",
            previous: $previous,
        );
    }

    public static function unsupportedStructure(string $path): self
    {
        return new self(
            "Lang file \"{$path}\" does not return a plain literal array and cannot be safely merged.",
        );
    }

    public static function invalidJson(string $path): self
    {
        return new self("Lang file \"{$path}\" does not contain a valid JSON object.");
    }
}
