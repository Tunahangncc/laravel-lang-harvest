<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Keys;

/**
 * The result of classifying a harvested string value into either a
 * group/dotted key or a plain JSON text key.
 */
final readonly class ClassifiedKey
{
    /**
     * @param  array<int, string>  $groupSegments  Nested path inside the group file, empty for JSON text keys.
     */
    public function __construct(
        public TranslationKeyType $type,
        public string $rawValue,
        public ?string $group = null,
        public array $groupSegments = [],
    ) {}

    /**
     * @param  array<int, string>  $segments
     */
    public static function group(string $rawValue, string $group, array $segments): self
    {
        return new self(TranslationKeyType::Group, $rawValue, $group, $segments);
    }

    public static function jsonText(string $rawValue): self
    {
        return new self(TranslationKeyType::JsonText, $rawValue);
    }
}
