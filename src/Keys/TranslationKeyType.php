<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Keys;

enum TranslationKeyType: string
{
    /**
     * A dotted key such as "arac.lastik-gecmisi" that targets a
     * lang/{locale}/{group}.php file.
     */
    case Group = 'group';

    /**
     * A plain text string that targets lang/{locale}.json as an
     * identity key (source text used as its own key).
     */
    case JsonText = 'json';
}
