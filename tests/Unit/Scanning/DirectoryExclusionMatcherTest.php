<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Scanning\DirectoryExclusionMatcher;

it('matches the excluded directory itself', function () {
    $matcher = new DirectoryExclusionMatcher(['/app/vendor']);

    expect($matcher->matches('/app/vendor'))->toBeTrue();
});

it('matches a path nested inside an excluded directory', function () {
    $matcher = new DirectoryExclusionMatcher(['/app/vendor']);

    expect($matcher->matches('/app/vendor/some-package/File.php'))->toBeTrue();
});

it('does not match an unrelated path', function () {
    $matcher = new DirectoryExclusionMatcher(['/app/vendor']);

    expect($matcher->matches('/app/src/File.php'))->toBeFalse();
});

it('does not match a sibling directory with a similar name prefix', function () {
    $matcher = new DirectoryExclusionMatcher(['/app/storage']);

    expect($matcher->matches('/app/storage-backup/Old.php'))->toBeFalse();
});

it('normalizes a trailing slash on the excluded directory', function () {
    $matcher = new DirectoryExclusionMatcher(['/app/vendor/']);

    expect($matcher->matches('/app/vendor/some-package/File.php'))->toBeTrue();
});

it('returns false for an empty exclusion list', function () {
    $matcher = new DirectoryExclusionMatcher([]);

    expect($matcher->matches('/anything'))->toBeFalse();
});
