<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Extraction;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Parses PHP source code and returns every __(), trans(), trans_choice(),
 * and Lang::get() call site found in it.
 */
final readonly class PhpFileExtractor
{
    private Parser $parser;

    public function __construct(
        private ArgumentResolver $argumentResolver = new ArgumentResolver,
    ) {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    /**
     * @return array<int, TranslationCall>
     *
     * @throws Error if the given code cannot be parsed as valid PHP.
     */
    public function extract(string $code): array
    {
        $statements = $this->parser->parse($code);

        if ($statements === null) {
            return [];
        }

        $visitor = new TranslationCallVisitor($this->argumentResolver);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        return $visitor->calls;
    }
}
