<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Keys\KeyClassifier;
use LaravelLangHarvest\LaravelLangHarvest\Keys\TranslationKeyType;

it('classifies a two-segment dotted key as a group key', function () {
    $classified = (new KeyClassifier)->classify('arac.lastik-gecmisi');

    expect($classified->type)->toBe(TranslationKeyType::Group)
        ->and($classified->group)->toBe('arac')
        ->and($classified->groupSegments)->toBe(['lastik-gecmisi'])
        ->and($classified->rawValue)->toBe('arac.lastik-gecmisi');
});

it('classifies a three-segment dotted key with a nested path', function () {
    $classified = (new KeyClassifier)->classify('profile.edit.title');

    expect($classified->type)->toBe(TranslationKeyType::Group)
        ->and($classified->group)->toBe('profile')
        ->and($classified->groupSegments)->toBe(['edit', 'title']);
});

it('classifies a sentence with spaces as a json text key', function () {
    $classified = (new KeyClassifier)->classify('Test mesaji :appName');

    expect($classified->type)->toBe(TranslationKeyType::JsonText)
        ->and($classified->rawValue)->toBe('Test mesaji :appName')
        ->and($classified->group)->toBeNull()
        ->and($classified->groupSegments)->toBe([]);
});

it('classifies a plain sentence ending in a period as a json text key', function () {
    $classified = (new KeyClassifier)->classify('Deneme test mesaji.');

    expect($classified->type)->toBe(TranslationKeyType::JsonText);
});

it('classifies a multi-sentence string as a json text key', function () {
    $classified = (new KeyClassifier)->classify('Birinci Cumle. Ikinci Cumle');

    expect($classified->type)->toBe(TranslationKeyType::JsonText);
});

it('classifies a single word without a dot as a json text key', function () {
    $classified = (new KeyClassifier)->classify('welcome');

    expect($classified->type)->toBe(TranslationKeyType::JsonText);
});

it('classifies a malformed dotted value with empty segments as a json text key', function () {
    $classified = (new KeyClassifier)->classify('arac..lastik-gecmisi');

    expect($classified->type)->toBe(TranslationKeyType::JsonText);
});
