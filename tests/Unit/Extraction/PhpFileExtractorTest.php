<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Extraction\PhpFileExtractor;

it('extracts a static __() call', function () {
    $calls = (new PhpFileExtractor)->extract("<?php\n__('arac.lastik-gecmisi');\n");

    expect($calls)->toHaveCount(1);
    expect($calls[0]->callee)->toBe('__');
    expect($calls[0]->argument->isStatic)->toBeTrue();
    expect($calls[0]->argument->value)->toBe('arac.lastik-gecmisi');
    expect($calls[0]->line)->toBe(2);
});

it('extracts a static trans() call', function () {
    $calls = (new PhpFileExtractor)->extract("<?php trans('profile.edit.title');");

    expect($calls)->toHaveCount(1);
    expect($calls[0]->callee)->toBe('trans');
    expect($calls[0]->argument->value)->toBe('profile.edit.title');
});

it('extracts a static trans_choice() call', function () {
    $calls = (new PhpFileExtractor)->extract("<?php trans_choice('arac.km-uyarisi', 1);");

    expect($calls)->toHaveCount(1);
    expect($calls[0]->callee)->toBe('trans_choice');
    expect($calls[0]->argument->value)->toBe('arac.km-uyarisi');
});

it('extracts a static Lang::get() call', function () {
    $calls = (new PhpFileExtractor)->extract("<?php Lang::get('arac.lastik-gecmisi');");

    expect($calls)->toHaveCount(1);
    expect($calls[0]->callee)->toBe('Lang::get');
    expect($calls[0]->argument->value)->toBe('arac.lastik-gecmisi');
});

it('extracts a fully qualified \\Illuminate\\Support\\Facades\\Lang::get() call', function () {
    $calls = (new PhpFileExtractor)->extract("<?php \\Illuminate\\Support\\Facades\\Lang::get('arac.lastik-gecmisi');");

    expect($calls)->toHaveCount(1);
    expect($calls[0]->callee)->toBe('Lang::get');
    expect($calls[0]->argument->value)->toBe('arac.lastik-gecmisi');
});

it('records a call with a dynamic variable argument for reporting', function () {
    $calls = (new PhpFileExtractor)->extract('<?php __($message);');

    expect($calls)->toHaveCount(1);
    expect($calls[0]->argument->isStatic)->toBeFalse();
    expect($calls[0]->argument->value)->toBeNull();
});

it('records a call with an interpolated string argument as dynamic', function () {
    $calls = (new PhpFileExtractor)->extract('<?php __("arac.{$type}");');

    expect($calls)->toHaveCount(1);
    expect($calls[0]->argument->isStatic)->toBeFalse();
});

it('ignores unrelated function calls', function () {
    $calls = (new PhpFileExtractor)->extract("<?php some_other_function('arac.lastik-gecmisi');");

    expect($calls)->toBeEmpty();
});

it('ignores static calls to unrelated classes named get', function () {
    $calls = (new PhpFileExtractor)->extract("<?php Foo::get('arac.lastik-gecmisi');");

    expect($calls)->toBeEmpty();
});

it('finds multiple calls with correct line numbers', function () {
    $code = <<<'PHP'
    <?php

    function show()
    {
        $first = __('arac.lastik-gecmisi');
        $second = trans('profile.edit.title');

        return $first . $second;
    }
    PHP;

    $calls = (new PhpFileExtractor)->extract($code);

    expect($calls)->toHaveCount(2)
        ->and($calls[0]->line)->toBe(5)
        ->and($calls[1]->line)->toBe(6);
});
