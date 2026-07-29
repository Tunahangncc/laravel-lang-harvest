<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Extraction\BalancedParenthesesReader;

it('finds the closing parenthesis of a simple call', function () {
    $text = "foo('bar')";
    $offset = strpos($text, '(') + 1;

    $closing = (new BalancedParenthesesReader)->findClosingParenthesis($text, $offset);

    expect($closing)->toBe(strrpos($text, ')'));
});

it('skips over nested parentheses', function () {
    $text = 'foo(bar(1,2), 3)';
    $offset = strpos($text, '(') + 1;

    $closing = (new BalancedParenthesesReader)->findClosingParenthesis($text, $offset);

    expect($closing)->toBe(strlen($text) - 1);
});

it('ignores parentheses inside a string literal', function () {
    $text = "foo('a(b)c')";
    $offset = strpos($text, '(') + 1;

    $closing = (new BalancedParenthesesReader)->findClosingParenthesis($text, $offset);

    expect($closing)->toBe(strlen($text) - 1);
});

it('handles an escaped quote inside a single-quoted string', function () {
    $text = "foo('it\\'s ok')";
    $offset = strpos($text, '(') + 1;

    $closing = (new BalancedParenthesesReader)->findClosingParenthesis($text, $offset);

    expect($closing)->toBe(strlen($text) - 1);
});

it('handles a double-quoted string containing a single quote and parentheses', function () {
    $text = 'foo("it\'s (ok)")';
    $offset = strpos($text, '(') + 1;

    $closing = (new BalancedParenthesesReader)->findClosingParenthesis($text, $offset);

    expect($closing)->toBe(strlen($text) - 1);
});

it('returns null when the parentheses never close', function () {
    $text = "foo('bar'";
    $offset = strpos($text, '(') + 1;

    $closing = (new BalancedParenthesesReader)->findClosingParenthesis($text, $offset);

    expect($closing)->toBeNull();
});
