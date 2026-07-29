<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Extraction;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;

/**
 * Decides whether a translation call's first argument is a plain string
 * literal (a harvestable key or message) or a dynamic expression that must
 * be skipped and reported instead.
 *
 * Only a bare single/double quoted string with no interpolation counts as
 * static. Everything else -- variables, concatenation, interpolated
 * strings, method calls, constants, and so on -- is treated as dynamic.
 */
final class ArgumentResolver
{
    public function resolve(Expr $expression): LiteralArgument
    {
        if ($expression instanceof String_) {
            return LiteralArgument::static($expression->value);
        }

        return LiteralArgument::dynamic();
    }
}
