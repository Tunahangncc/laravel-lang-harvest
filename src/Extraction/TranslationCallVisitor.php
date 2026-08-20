<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Extraction;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeVisitorAbstract;

/**
 * Walks a parsed PHP AST and collects every call to a supported
 * translation function or facade method, classifying its first
 * argument along the way.
 *
 * Every matched call site is recorded, even when the first argument is
 * dynamic or missing, so callers can report on it (e.g. --check). A
 * call whose argument is a ternary with only static branches (e.g.
 * `$cond ? 'Edit Post' : 'Create Post'`) produces one call entry per
 * branch, since every possible outcome is harvestable.
 *
 * @internal Used by PhpFileExtractor; not part of the public API.
 */
final class TranslationCallVisitor extends NodeVisitorAbstract
{
    /** @var array<int, string> */
    private const array FUNCTION_NAMES = ['__', 'trans', 'trans_choice'];

    /** @var array<int, TranslationCall> */
    public array $calls = [];

    public function __construct(
        private readonly ArgumentResolver $argumentResolver = new ArgumentResolver,
    ) {}

    public function enterNode(Node $node): null
    {
        if ($node instanceof FuncCall) {
            $this->visitFuncCall($node);
        }

        if ($node instanceof StaticCall) {
            $this->visitStaticCall($node);
        }

        return null;
    }

    private function visitFuncCall(FuncCall $node): void
    {
        if (! $node->name instanceof Node\Name) {
            return;
        }

        $name = $node->name->toString();

        if (! in_array($name, self::FUNCTION_NAMES, true)) {
            return;
        }

        $this->recordCall($node, $name);
    }

    private function visitStaticCall(StaticCall $node): void
    {
        if (! $node->class instanceof Node\Name) {
            return;
        }

        if (! $node->name instanceof Node\Identifier) {
            return;
        }

        if ($node->class->getLast() !== 'Lang' || $node->name->toString() !== 'get') {
            return;
        }

        $this->recordCall($node, 'Lang::get');
    }

    private function recordCall(FuncCall|StaticCall $node, string $callee): void
    {
        foreach ($this->resolveArguments($node) as $argument) {
            $this->calls[] = new TranslationCall(
                callee: $callee,
                argument: $argument,
                line: $node->getStartLine(),
            );
        }
    }

    /**
     * @return array<int, LiteralArgument>
     */
    private function resolveArguments(FuncCall|StaticCall $node): array
    {
        $args = $node->args;

        if (! isset($args[0]) || ! $args[0] instanceof Node\Arg) {
            return [LiteralArgument::dynamic()];
        }

        $resolved = $this->argumentResolver->resolveAll($args[0]->value);

        return $resolved === [] ? [LiteralArgument::dynamic()] : $resolved;
    }
}
