<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\LaravelLangHarvest;

it('resolves the singleton', function () {
    expect(app(LaravelLangHarvest::class))->toBeInstanceOf(LaravelLangHarvest::class);
});

it('returns the same instance from the container', function () {
    expect(app(LaravelLangHarvest::class))->toBe(app(LaravelLangHarvest::class));
});

it('merges the package config with the expected defaults', function () {
    expect(config('laravel-lang-harvest.paths'))->toBe(['app', 'resources/views'])
        ->and(config('laravel-lang-harvest.exclude'))->toBe(['vendor', 'node_modules', 'storage', 'bootstrap/cache', 'tests'])
        ->and(config('laravel-lang-harvest.placeholder'))->toBe('[%s]');
});

it('harvests a missing key into the lang files by default', function () {
    $sourceDir = sys_get_temp_dir().'/lang-harvest-feature-'.bin2hex(random_bytes(6));
    $langDir = sys_get_temp_dir().'/lang-harvest-feature-lang-'.bin2hex(random_bytes(6));
    mkdir($sourceDir, recursive: true);
    mkdir($langDir, recursive: true);
    file_put_contents($sourceDir.'/Controller.php', "<?php\n\nreturn __('arac.lastik-gecmisi');\n");

    config([
        'laravel-lang-harvest.paths' => [$sourceDir],
        'laravel-lang-harvest.exclude' => [],
        'laravel-lang-harvest.locale' => 'en',
    ]);

    app()->useLangPath($langDir);

    try {
        $this->artisan('lang:harvest')
            ->expectsOutputToContain('Taranan dosya sayısı: 1')
            ->assertSuccessful();

        expect(file_get_contents($langDir.'/en/arac.php'))
            ->toContain("'lastik-gecmisi' => '[arac.lastik-gecmisi]'");
    } finally {
        @unlink($sourceDir.'/Controller.php');
        @rmdir($sourceDir);
        @unlink($langDir.'/en/arac.php');
        @rmdir($langDir.'/en');
        @rmdir($langDir);
    }
});

it('fails with --check when there are missing keys, without writing files', function () {
    $sourceDir = sys_get_temp_dir().'/lang-harvest-feature-'.bin2hex(random_bytes(6));
    $langDir = sys_get_temp_dir().'/lang-harvest-feature-lang-'.bin2hex(random_bytes(6));
    mkdir($sourceDir, recursive: true);
    mkdir($langDir, recursive: true);
    file_put_contents($sourceDir.'/Controller.php', "<?php\n\nreturn __('arac.lastik-gecmisi');\n");

    config([
        'laravel-lang-harvest.paths' => [$sourceDir],
        'laravel-lang-harvest.exclude' => [],
        'laravel-lang-harvest.locale' => 'en',
    ]);

    app()->useLangPath($langDir);

    try {
        $this->artisan('lang:harvest', ['--check' => true])->assertFailed();

        expect(is_file($langDir.'/en/arac.php'))->toBeFalse();
    } finally {
        @unlink($sourceDir.'/Controller.php');
        @rmdir($sourceDir);
        @rmdir($langDir);
    }
});
