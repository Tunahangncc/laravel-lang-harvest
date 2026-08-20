<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Extraction;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Ternary;
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

    /**
     * Resolves an expression to every statically-known string it could
     * produce at run time.
     *
     * A plain string literal resolves to itself. A full ternary
     * (`cond ? a : b`, not the short `cond ?: b` form) resolves to the
     * union of both branches when *both* are themselves fully resolvable
     * -- covering the common `$cond ? 'Edit Post' : 'Create Post'`
     * pattern, including nested ternaries. If either branch cannot be
     * resolved, the whole expression is treated as dynamic: we never
     * harvest half of a call site.
     *
     * Anything else that cannot be resolved returns an empty array,
     * signalling "dynamic" to the caller.
     *
     * @return array<int, LiteralArgument>
     */
    public function resolveAll(Expr $expression): array
    {
        if ($expression instanceof Ternary && $expression->if !== null) {
            $ifResults = $this->resolveAll($expression->if);
            $elseResults = $this->resolveAll($expression->else);

            if ($ifResults === [] || $elseResults === []) {
                return [];
            }

            return [...$ifResults, ...$elseResults];
        }

        $resolved = $this->resolve($expression);

        return $resolved->isStatic ? [$resolved] : [];
    }
}
