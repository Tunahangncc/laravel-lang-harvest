<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Writing\GroupFileMerger;
use LaravelLangHarvest\LaravelLangHarvest\Writing\GroupKeyEntry;

it('adds a missing flat key', function () {
    $entry = new GroupKeyEntry('arac.lastik-gecmisi', ['lastik-gecmisi'], '[arac.lastik-gecmisi]');

    $result = (new GroupFileMerger)->merge([], [$entry]);

    expect($result->data)->toBe(['lastik-gecmisi' => '[arac.lastik-gecmisi]'])
        ->and($result->added)->toBe([$entry])
        ->and($result->conflicts)->toBe([]);
});

it('does not overwrite an existing flat key', function () {
    $entry = new GroupKeyEntry('arac.lastik-gecmisi', ['lastik-gecmisi'], '[arac.lastik-gecmisi]');
    $existing = ['lastik-gecmisi' => 'Lastik Gecmisi'];

    $result = (new GroupFileMerger)->merge($existing, [$entry]);

    expect($result->data)->toBe(['lastik-gecmisi' => 'Lastik Gecmisi'])
        ->and($result->added)->toBe([])
        ->and($result->hasChanges())->toBeFalse();
});

it('creates a nested path when it is entirely missing', function () {
    $entry = new GroupKeyEntry('profile.edit.title', ['edit', 'title'], '[profile.edit.title]');

    $result = (new GroupFileMerger)->merge([], [$entry]);

    expect($result->data)->toBe(['edit' => ['title' => '[profile.edit.title]']])
        ->and($result->added)->toBe([$entry]);
});

it('adds a nested key under an existing partial path without touching siblings', function () {
    $entry = new GroupKeyEntry('profile.edit.title', ['edit', 'title'], '[profile.edit.title]');
    $existing = ['edit' => ['description' => 'Duzenleme aciklamasi']];

    $result = (new GroupFileMerger)->merge($existing, [$entry]);

    expect($result->data)->toBe([
        'edit' => [
            'description' => 'Duzenleme aciklamasi',
            'title' => '[profile.edit.title]',
        ],
    ]);
});

it('does not overwrite an existing nested key', function () {
    $entry = new GroupKeyEntry('profile.edit.title', ['edit', 'title'], '[profile.edit.title]');
    $existing = ['edit' => ['title' => 'Duzenle']];

    $result = (new GroupFileMerger)->merge($existing, [$entry]);

    expect($result->data)->toBe(['edit' => ['title' => 'Duzenle']])
        ->and($result->hasChanges())->toBeFalse();
});

it('reports a conflict when a path segment already exists as a non-array value', function () {
    $entry = new GroupKeyEntry('profile.edit.title', ['edit', 'title'], '[profile.edit.title]');
    $existing = ['edit' => 'Duzenle'];

    $result = (new GroupFileMerger)->merge($existing, [$entry]);

    expect($result->data)->toBe(['edit' => 'Duzenle'])
        ->and($result->added)->toBe([])
        ->and($result->conflicts)->toBe([$entry]);
});

it('merges several entries in one call, mixing added, existing, and conflicting keys', function () {
    $entries = [
        new GroupKeyEntry('arac.lastik-gecmisi', ['lastik-gecmisi'], '[arac.lastik-gecmisi]'),
        new GroupKeyEntry('arac.km-uyarisi', ['km-uyarisi'], '[arac.km-uyarisi]'),
        new GroupKeyEntry('arac.bakim.tarihi', ['bakim', 'tarihi'], '[arac.bakim.tarihi]'),
    ];
    $existing = [
        'km-uyarisi' => 'KM Uyarisi',
        'bakim' => 'Bakim',
    ];

    $result = (new GroupFileMerger)->merge($existing, $entries);

    expect($result->data)->toBe([
        'km-uyarisi' => 'KM Uyarisi',
        'bakim' => 'Bakim',
        'lastik-gecmisi' => '[arac.lastik-gecmisi]',
    ])
        ->and($result->added)->toBe([$entries[0]])
        ->and($result->conflicts)->toBe([$entries[2]]);
});
