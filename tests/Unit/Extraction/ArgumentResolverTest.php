<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Extraction\ArgumentResolver;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Expression;
use PhpParser\ParserFactory;

/**
 * Parses `<?php {$code};` and returns the first argument expression of the
 * single top-level function call statement it contains.
 */
function firstCallArgument(string $code): Expr
{
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    $statements = $parser->parse("<?php {$code};");

    expect($statements)->not->toBeNull();

    $expressionStatement = $statements[0];

    expect($expressionStatement)->toBeInstanceOf(Expression::class);

    $call = $expressionStatement->expr;

    expect($call)->toBeInstanceOf(FuncCall::class);

    /** @var Arg $firstArg */
    $firstArg = $call->args[0];

    return $firstArg->value;
}

it('classifies a single-quoted string literal as static', function () {
    $argument = (new ArgumentResolver)->resolve(firstCallArgument("__('arac.lastik-gecmisi')"));

    expect($argument->isStatic)->toBeTrue()
        ->and($argument->value)->toBe('arac.lastik-gecmisi');
});

it('classifies a double-quoted string literal without interpolation as static', function () {
    $argument = (new ArgumentResolver)->resolve(firstCallArgument('__("Deneme test mesaji.")'));

    expect($argument->isStatic)->toBeTrue()
        ->and($argument->value)->toBe('Deneme test mesaji.');
});

it('keeps Laravel-style colon placeholders as part of the static value', function () {
    $argument = (new ArgumentResolver)->resolve(firstCallArgument('__("Test mesaji :appName")'));

    expect($argument->isStatic)->toBeTrue()
        ->and($argument->value)->toBe('Test mesaji :appName');
});

it('classifies a variable argument as dynamic', function () {
    $argument = (new ArgumentResolver)->resolve(firstCallArgument('trans($key)'));

    expect($argument->isStatic)->toBeFalse()
        ->and($argument->value)->toBeNull();
});

it('classifies an interpolated string argument as dynamic', function () {
    $argument = (new ArgumentResolver)->resolve(firstCallArgument('__("arac.{$type}")'));

    expect($argument->isStatic)->toBeFalse()
        ->and($argument->value)->toBeNull();
});

it('classifies a concatenated string argument as dynamic', function () {
    $argument = (new ArgumentResolver)->resolve(firstCallArgument("trans('arac.' . \$suffix)"));

    expect($argument->isStatic)->toBeFalse()
        ->and($argument->value)->toBeNull();
});
