<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Extraction;

/**
 * The result of classifying a single call argument expression.
 */
final readonly class LiteralArgument
{
    public function __construct(
        public bool $isStatic,
        public ?string $value,
    ) {}

    public static function static(string $value): self
    {
        return new self(isStatic: true, value: $value);
    }

    public static function dynamic(): self
    {
        return new self(isStatic: false, value: null);
    }
}
