<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Writing\JsonLangFileWriter;
use LaravelLangHarvest\LaravelLangHarvest\Writing\UnreadableLangFileException;

function tempJsonPath(): string
{
    return sys_get_temp_dir().'/lang-harvest-json-'.bin2hex(random_bytes(6)).'.json';
}

it('adds every text as an identity key when no file exists yet', function () {
    $path = tempJsonPath();

    $result = (new JsonLangFileWriter)->merge($path, ['Test mesaji :appName', 'Deneme test mesaji.']);

    expect($result->data)->toBe([
        'Test mesaji :appName' => 'Test mesaji :appName',
        'Deneme test mesaji.' => 'Deneme test mesaji.',
    ])->and($result->added)->toBe(['Test mesaji :appName', 'Deneme test mesaji.']);
});

it('does not overwrite an existing translated value', function () {
    $path = tempJsonPath();
    file_put_contents($path, json_encode(['Merhaba' => 'Hello'], JSON_UNESCAPED_UNICODE));

    try {
        $result = (new JsonLangFileWriter)->merge($path, ['Merhaba', 'Yeni mesaj']);

        expect($result->data)->toBe([
            'Merhaba' => 'Hello',
            'Yeni mesaj' => 'Yeni mesaj',
        ])->and($result->added)->toBe(['Yeni mesaj']);
    } finally {
        unlink($path);
    }
});

it('treats a missing file as an empty map', function () {
    $path = tempJsonPath();

    $result = (new JsonLangFileWriter)->merge($path, []);

    expect($result->data)->toBe([])->and($result->hasChanges())->toBeFalse();
});

it('treats an empty existing file as an empty map', function () {
    $path = tempJsonPath();
    file_put_contents($path, '');

    try {
        $result = (new JsonLangFileWriter)->merge($path, ['Merhaba']);

        expect($result->data)->toBe(['Merhaba' => 'Merhaba']);
    } finally {
        unlink($path);
    }
});

it('throws when the existing file is not a valid JSON object', function () {
    $path = tempJsonPath();
    file_put_contents($path, '["a", "b"]');

    try {
        (new JsonLangFileWriter)->merge($path, ['Merhaba']);
    } finally {
        unlink($path);
    }
})->throws(UnreadableLangFileException::class);

it('writes pretty-printed JSON with unicode characters preserved', function () {
    $path = tempJsonPath();

    try {
        (new JsonLangFileWriter)->write($path, ['Merhaba dünya' => 'Merhaba dünya']);

        $contents = file_get_contents($path);

        expect($contents)->toContain('Merhaba dünya')
            ->and($contents)->not->toContain('\\u')
            ->and(json_decode($contents, true))->toBe(['Merhaba dünya' => 'Merhaba dünya']);
    } finally {
        unlink($path);
    }
});
