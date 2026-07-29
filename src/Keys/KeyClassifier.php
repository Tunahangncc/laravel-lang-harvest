<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Keys;

/**
 * Decides whether a harvested string is a dotted/group key (targets
 * lang/{locale}/{group}.php) or a plain text string (targets
 * lang/{locale}.json as an identity key).
 */
final class KeyClassifier
{
    private const string GROUP_KEY_PATTERN = '/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)+$/';

    public function classify(string $value): ClassifiedKey
    {
        if (preg_match(self::GROUP_KEY_PATTERN, $value) === 1) {
            $segments = explode('.', $value);
            $group = array_shift($segments);

            return ClassifiedKey::group($value, $group, $segments);
        }

        return ClassifiedKey::jsonText($value);
    }
}
