<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\LaravelLangHarvest;

it('resolves the singleton', function () {
    expect(app(LaravelLangHarvest::class))->toBeInstanceOf(LaravelLangHarvest::class);
});

it('returns the same instance from the container', function () {
    expect(app(LaravelLangHarvest::class))->toBe(app(LaravelLangHarvest::class));
});

it('merges the package config', function () {
    expect(config('laravel-lang-harvest.placeholder'))->toBe('default');
});

it('registers the artisan command', function () {
    $this->artisan('laravel-lang-harvest:placeholder')
        ->expectsOutputToContain('LaravelLangHarvest placeholder command executed.')
        ->assertSuccessful();
});
