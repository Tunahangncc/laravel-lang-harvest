<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Writing;

use PhpParser\Error as PhpParserError;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Reads an existing lang/{locale}/{group}.php file into a nested
 * associative array, without executing the file.
 *
 * Only a single `return [...];` statement containing a literal array of
 * string/int keys and string/array values is supported. This covers
 * every lang file this package generates and the overwhelming majority
 * of handwritten ones. Anything else (dynamic expressions, includes,
 * function calls) is reported as unreadable so the caller can skip the
 * file rather than risk misinterpreting or corrupting it.
 */
final readonly class PhpArrayFileReader
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws UnreadableLangFileException
     */
    public function read(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $code = file_get_contents($path);

        if ($code === false) {
            throw UnreadableLangFileException::unreadable($path);
        }

        try {
            $statements = $this->parser->parse($code) ?? [];
        } catch (PhpParserError $error) {
            throw UnreadableLangFileException::invalidSyntax($path, $error);
        }

        $returnStatement = $this->findReturnStatement($statements);

        if ($returnStatement === null || ! $returnStatement->expr instanceof Array_) {
            throw UnreadableLangFileException::unsupportedStructure($path);
        }

        return $this->evaluateArray($returnStatement->expr, $path);
    }

    /**
     * @param  array<int, Stmt>  $statements
     */
    private function findReturnStatement(array $statements): ?Return_
    {
        foreach ($statements as $statement) {
            if ($statement instanceof Return_) {
                return $statement;
            }
        }

        return null;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function evaluateArray(Array_ $array, string $path): array
    {
        $result = [];

        foreach ($array->items as $item) {
            $value = $this->evaluateValue($item->value, $path);

            if ($item->key === null) {
                $result[] = $value;

                continue;
            }

            $key = $this->evaluateValue($item->key, $path);

            if (! is_string($key) && ! is_int($key)) {
                throw UnreadableLangFileException::unsupportedStructure($path);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private function evaluateValue(Expr $expr, string $path): mixed
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        if ($expr instanceof Node\Scalar\Int_) {
            return $expr->value;
        }

        if ($expr instanceof Array_) {
            return $this->evaluateArray($expr, $path);
        }

        throw UnreadableLangFileException::unsupportedStructure($path);
    }
}
