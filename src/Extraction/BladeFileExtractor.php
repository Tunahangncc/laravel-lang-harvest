<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Extraction;

use PhpParser\Error as PhpParserError;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Finds translation calls in .blade.php source.
 *
 * A .blade.php file is not valid PHP as a whole (it mixes HTML with
 * directives and echo syntax), so we cannot parse the full file into a
 * single AST like PhpFileExtractor does. Instead we locate call sites by
 * name in the raw text, extract their argument list with
 * BalancedParenthesesReader, and parse just that argument list as an
 * isolated PHP expression to classify the first argument. This keeps
 * line numbers identical to the source file and avoids needing to run
 * Blade's own compiler.
 */
final class BladeFileExtractor
{
    /**
     * Regex pattern => reported callee label.
     *
     * The (?<!->) (?<!::) (?<!$) guards avoid matching method calls,
     * unrelated static calls, and variable-variable calls that merely
     * happen to end in one of these names.
     *
     * @var array<string, string>
     */
    private const PATTERNS = [
        '/(?<!->)(?<!::)(?<!\$)\btrans_choice\(/' => 'trans_choice',
        '/(?<!->)(?<!::)(?<!\$)\btrans\(/' => 'trans',
        '/(?<!->)(?<!::)(?<!\$)\b__\(/' => '__',
        '/(?<!\$)\bLang::get\(/' => 'Lang::get',
        '/@lang\(/' => '@lang',
        '/@choice\(/' => '@choice',
    ];

    private readonly Parser $parser;

    public function __construct(
        private readonly ArgumentResolver $argumentResolver = new ArgumentResolver,
        private readonly BalancedParenthesesReader $parenthesesReader = new BalancedParenthesesReader,
    ) {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    /**
     * @return array<int, TranslationCall>
     */
    public function extract(string $code): array
    {
        $calls = [];

        foreach (self::PATTERNS as $pattern => $callee) {
            $calls = [...$calls, ...$this->findCalls($code, $pattern, $callee)];
        }

        usort($calls, static fn (TranslationCall $a, TranslationCall $b): int => $a->line <=> $b->line);

        return $calls;
    }

    /**
     * @return array<int, TranslationCall>
     */
    private function findCalls(string $code, string $pattern, string $callee): array
    {
        if (preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE) === 0) {
            return [];
        }

        $calls = [];

        foreach ($matches[0] as [$matchedText, $matchOffset]) {
            $openingParenOffset = $matchOffset + strlen($matchedText) - 1;
            $closingParenOffset = $this->parenthesesReader->findClosingParenthesis($code, $openingParenOffset + 1);

            if ($closingParenOffset === null) {
                continue;
            }

            $argumentsText = substr(
                $code,
                $openingParenOffset + 1,
                $closingParenOffset - $openingParenOffset - 1,
            );

            $line = substr_count($code, "\n", 0, $matchOffset) + 1;

            $calls[] = new TranslationCall(
                callee: $callee,
                argument: $this->classifyArguments($argumentsText),
                line: $line,
            );
        }

        return $calls;
    }

    private function classifyArguments(string $argumentsText): LiteralArgument
    {
        try {
            $statements = $this->parser->parse("<?php __harvest_call__({$argumentsText});");
        } catch (PhpParserError) {
            return LiteralArgument::dynamic();
        }

        $firstStatement = $statements[0] ?? null;

        if (! $firstStatement instanceof Expression || ! $firstStatement->expr instanceof FuncCall) {
            return LiteralArgument::dynamic();
        }

        $args = $firstStatement->expr->args;

        if (! isset($args[0]) || ! $args[0] instanceof Node\Arg) {
            return LiteralArgument::dynamic();
        }

        return $this->argumentResolver->resolve($args[0]->value);
    }
}
