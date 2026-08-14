<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Extraction\BladeFileExtractor;

it('extracts a static __() call inside an echo block', function () {
    $calls = (new BladeFileExtractor)->extract("<div>{{ __('arac.lastik-gecmisi') }}</div>");

    expect($calls)->toHaveCount(1)
        ->and($calls[0]->callee)->toBe('__')
        ->and($calls[0]->argument->isStatic)->toBeTrue()
        ->and($calls[0]->argument->value)->toBe('arac.lastik-gecmisi');
});

it('extracts a static trans() call inside an echo block', function () {
    $calls = (new BladeFileExtractor)->extract("<h1>{{ trans('profile.edit.title') }}</h1>");

    expect($calls)->toHaveCount(1)
        ->and($calls[0]->callee)->toBe('trans')
        ->and($calls[0]->argument->value)->toBe('profile.edit.title');
});

it('extracts a static @lang() directive', function () {
    $calls = (new BladeFileExtractor)->extract("<p>@lang('arac.lastik-gecmisi')</p>");

    expect($calls)->toHaveCount(1)
        ->and($calls[0]->callee)->toBe('@lang')
        ->and($calls[0]->argument->value)->toBe('arac.lastik-gecmisi');
});

it('extracts a static @choice() directive, ignoring the count argument', function () {
    $calls = (new BladeFileExtractor)->extract("<p>@choice('arac.km-uyarisi', \$count)</p>");

    expect($calls)->toHaveCount(1)
        ->and($calls[0]->callee)->toBe('@choice')
        ->and($calls[0]->argument->value)->toBe('arac.km-uyarisi');
});

it('extracts a static Lang::get() call inside a raw php block', function () {
    $calls = (new BladeFileExtractor)->extract("<?php \$title = Lang::get('arac.lastik-gecmisi'); ?>");

    expect($calls)->toHaveCount(1)
        ->and($calls[0]->callee)->toBe('Lang::get')
        ->and($calls[0]->argument->value)->toBe('arac.lastik-gecmisi');
});

it('records a dynamic variable argument for reporting', function () {
    $calls = (new BladeFileExtractor)->extract('<div>{{ __($message) }}</div>');

    expect($calls)->toHaveCount(1)
        ->and($calls[0]->argument->isStatic)->toBeFalse();
});

it('records an interpolated @lang() argument as dynamic', function () {
    $calls = (new BladeFileExtractor)->extract('<p>@lang("arac.{$type}")</p>');

    expect($calls)->toHaveCount(1)
        ->and($calls[0]->callee)->toBe('@lang')
        ->and($calls[0]->argument->isStatic)->toBeFalse();
});

it('ignores a trans() method call on an object', function () {
    $calls = (new BladeFileExtractor)->extract("<div>{{ \$translator->trans('arac.lastik-gecmisi') }}</div>");

    expect($calls)->toBeEmpty();
});

it('finds multiple calls in a mixed template with correct line numbers', function () {
    $code = <<<'BLADE'
    <div class="card">
        <h1>{{ __('arac.lastik-gecmisi') }}</h1>
        <p>@lang('profile.edit.title')</p>
    </div>
    BLADE;

    $calls = (new BladeFileExtractor)->extract($code);

    expect($calls)->toHaveCount(2)
        ->and($calls[0]->line)->toBe(2)
        ->and($calls[0]->callee)->toBe('__')
        ->and($calls[1]->line)->toBe(3)
        ->and($calls[1]->callee)->toBe('@lang');
});

it('splits a real-world @lang() ternary into two harvestable calls', function () {
    $calls = (new BladeFileExtractor)->extract("<div>\n    @lang(isset(\$post) ? 'Edit Post' : 'Create New Post')\n</div>");

    expect($calls)->toHaveCount(2);
    expect($calls[0]->callee)->toBe('@lang');
    expect($calls[0]->argument->value)->toBe('Edit Post');
    expect($calls[1]->callee)->toBe('@lang');
    expect($calls[1]->argument->value)->toBe('Create New Post');
});
