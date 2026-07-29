<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Extraction;

/**
 * Given source text and the offset of the character right after an
 * opening "(", finds the offset of its matching closing ")".
 *
 * Tracks single/double quoted string literals (including backslash
 * escapes) so that a "(" or ")" inside a string does not affect the
 * depth count, and tracks nested parentheses so inner calls,
 * arrays, or grouped expressions are skipped over correctly.
 */
final class BalancedParenthesesReader
{
    /**
     * @return int|null The offset of the matching ")", or null if the
     *                  parentheses never balance before the text ends.
     */
    public function findClosingParenthesis(string $text, int $offset): ?int
    {
        $length = strlen($text);
        $depth = 1;
        $quote = null;

        for ($i = $offset; $i < $length; $i++) {
            $char = $text[$i];

            if ($quote !== null) {
                if ($char === '\\') {
                    $i++;

                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '\'' || $char === '"') {
                $quote = $char;

                continue;
            }

            if ($char === '(') {
                $depth++;

                continue;
            }

            if ($char === ')') {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }
}
