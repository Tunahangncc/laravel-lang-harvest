<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Extraction;

/**
 * A single translation call found in a source file, with its first
 * argument already classified as static or dynamic.
 */
final readonly class TranslationCall
{
    public function __construct(
        public string $callee,
        public LiteralArgument $argument,
        public int $line,
    ) {}
}
