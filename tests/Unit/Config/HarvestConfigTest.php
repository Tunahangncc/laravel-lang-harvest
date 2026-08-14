<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Config\HarvestConfig;

it('uses the configured locale when present', function () {
    $config = HarvestConfig::fromArray('/app', ['locale' => 'tr'], 'en');

    expect($config->locale())->toBe('tr');
});

it('falls back to the application locale when none is configured', function () {
    $config = HarvestConfig::fromArray('/app', [], 'en');

    expect($config->locale())->toBe('en');
});

it('falls back to the application locale when the configured value is an empty string', function () {
    $config = HarvestConfig::fromArray('/app', ['locale' => ''], 'en');

    expect($config->locale())->toBe('en');
});

it('uses default scan paths and excludes when none are configured', function () {
    $config = HarvestConfig::fromArray('/app', [], 'en');

    expect($config->scanPaths())->toBe(['/app/app', '/app/resources/views'])
        ->and($config->excludedDirectories())->toBe([]);
});

it('resolves relative paths against the base path', function () {
    $config = HarvestConfig::fromArray('/app', [
        'paths' => ['app', 'Modules'],
        'exclude' => ['vendor', 'storage'],
    ], 'en');

    expect($config->scanPaths())->toBe(['/app/app', '/app/Modules'])
        ->and($config->excludedDirectories())->toBe(['/app/vendor', '/app/storage']);
});

it('leaves already-absolute paths untouched', function () {
    $config = HarvestConfig::fromArray('/app', [
        'paths' => ['/somewhere/else/app'],
        'exclude' => ['/somewhere/else/vendor'],
    ], 'en');

    expect($config->scanPaths())->toBe(['/somewhere/else/app'])
        ->and($config->excludedDirectories())->toBe(['/somewhere/else/vendor']);
});

it('does not produce a double slash when the base path has a trailing slash', function () {
    $config = HarvestConfig::fromArray('/app/', ['paths' => ['app']], 'en');

    expect($config->scanPaths())->toBe(['/app/app']);
});

it('formats the default placeholder', function () {
    $config = HarvestConfig::fromArray('/app', [], 'en');

    expect($config->placeholder('arac.lastik-gecmisi'))->toBe('[arac.lastik-gecmisi]');
});

it('formats a custom placeholder', function () {
    $config = HarvestConfig::fromArray('/app', ['placeholder' => 'TODO: %s'], 'en');

    expect($config->placeholder('arac.lastik-gecmisi'))->toBe('TODO: arac.lastik-gecmisi');
});
