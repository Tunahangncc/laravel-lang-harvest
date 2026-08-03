<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Writing\PhpArrayFileReader;
use LaravelLangHarvest\LaravelLangHarvest\Writing\UnreadableLangFileException;

function writeTempLangFile(string $contents): string
{
    $path = sys_get_temp_dir().'/lang-harvest-reader-'.bin2hex(random_bytes(6)).'.php';
    file_put_contents($path, $contents);

    return $path;
}

it('returns an empty array when the file does not exist', function () {
    $path = sys_get_temp_dir().'/lang-harvest-missing-'.bin2hex(random_bytes(6)).'.php';

    expect((new PhpArrayFileReader)->read($path))->toBe([]);
});

it('reads a flat literal array', function () {
    $path = writeTempLangFile("<?php\n\nreturn [\n    'lastik-gecmisi' => 'Lastik Gecmisi',\n];\n");

    try {
        expect((new PhpArrayFileReader)->read($path))->toBe([
            'lastik-gecmisi' => 'Lastik Gecmisi',
        ]);
    } finally {
        unlink($path);
    }
});

it('reads a nested literal array', function () {
    $path = writeTempLangFile("<?php\n\nreturn [\n    'edit' => [\n        'title' => 'Duzenle',\n    ],\n];\n");

    try {
        expect((new PhpArrayFileReader)->read($path))->toBe([
            'edit' => ['title' => 'Duzenle'],
        ]);
    } finally {
        unlink($path);
    }
});

it('throws when the file contains invalid PHP', function () {
    $path = writeTempLangFile("<?php\n\nreturn [\n");

    try {
        (new PhpArrayFileReader)->read($path);
    } catch (UnreadableLangFileException $exception) {
        expect($exception->getMessage())->toContain('invalid PHP');
    } finally {
        unlink($path);
    }
})->throws(UnreadableLangFileException::class);

it('throws when the file has no return statement', function () {
    $path = writeTempLangFile("<?php\n\n\$foo = 'bar';\n");

    try {
        (new PhpArrayFileReader)->read($path);
    } finally {
        unlink($path);
    }
})->throws(UnreadableLangFileException::class);

it('throws when the return value is not an array', function () {
    $path = writeTempLangFile("<?php\n\nreturn 'not-an-array';\n");

    try {
        (new PhpArrayFileReader)->read($path);
    } finally {
        unlink($path);
    }
})->throws(UnreadableLangFileException::class);

it('throws when a value is a dynamic expression', function () {
    $path = writeTempLangFile("<?php\n\nreturn [\n    'greeting' => some_function(),\n];\n");

    try {
        (new PhpArrayFileReader)->read($path);
    } finally {
        unlink($path);
    }
})->throws(UnreadableLangFileException::class);
