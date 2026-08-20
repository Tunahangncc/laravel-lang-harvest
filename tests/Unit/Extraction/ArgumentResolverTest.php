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

it('resolves a plain string via resolveAll', function () {
    $results = (new ArgumentResolver)->resolveAll(firstCallArgument("__('arac.lastik-gecmisi')"));

    expect($results)->toHaveCount(1)
        ->and($results[0]->value)->toBe('arac.lastik-gecmisi');
});

it('resolves a ternary with two static branches to both values', function () {
    $results = (new ArgumentResolver)->resolveAll(firstCallArgument("__(\$isEdit ? 'Edit Post' : 'Create New Post')"));

    expect($results)->toHaveCount(2)
        ->and($results[0]->value)->toBe('Edit Post')
        ->and($results[1]->value)->toBe('Create New Post');
});

it('resolves a nested ternary where every branch is static', function () {
    $results = (new ArgumentResolver)->resolveAll(firstCallArgument("__(\$a ? 'X' : (\$b ? 'Y' : 'Z'))"));

    expect($results)->toHaveCount(3)
        ->and(array_map(fn ($r) => $r->value, $results))->toBe(['X', 'Y', 'Z']);
});

it('treats a ternary with one dynamic branch as fully dynamic', function () {
    $results = (new ArgumentResolver)->resolveAll(firstCallArgument("__(\$cond ? \$var : 'Static')"));

    expect($results)->toBe([]);
});

it('treats a short ternary as fully dynamic', function () {
    $results = (new ArgumentResolver)->resolveAll(firstCallArgument("__(\$maybeKey ?: 'Default')"));

    expect($results)->toBe([]);
});

it('treats a plain variable as fully dynamic via resolveAll', function () {
    $results = (new ArgumentResolver)->resolveAll(firstCallArgument('trans($key)'));

    expect($results)->toBe([]);
});
