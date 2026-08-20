<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Writing\PhpArrayFileReader;
use LaravelLangHarvest\LaravelLangHarvest\Writing\PhpArrayFileRenderer;

it('renders an empty array', function () {
    $rendered = (new PhpArrayFileRenderer)->render([]);

    expect($rendered)->toBe("<?php\n\nreturn [];\n");
});

it('renders a flat array with escaped quotes and backslashes', function () {
    $rendered = (new PhpArrayFileRenderer)->render([
        'lastik-gecmisi' => "It's a \\test\\",
    ]);

    expect($rendered)->toBe(
        "<?php\n\nreturn [\n    'lastik-gecmisi' => 'It\\'s a \\\\test\\\\',\n];\n",
    );
});

it('renders a nested array with indentation', function () {
    $rendered = (new PhpArrayFileRenderer)->render([
        'edit' => ['title' => 'Duzenle'],
    ]);

    expect($rendered)->toBe(
        "<?php\n\nreturn [\n    'edit' => [\n        'title' => 'Duzenle',\n    ],\n];\n",
    );
});

it('round-trips a nested array through render and read', function () {
    $data = [
        'lastik-gecmisi' => '[arac.lastik-gecmisi]',
        'bakim' => [
            'tarihi' => '[arac.bakim.tarihi]',
            'aciklama' => "It's a \\test\\",
        ],
    ];

    $rendered = (new PhpArrayFileRenderer)->render($data);

    $path = sys_get_temp_dir().'/lang-harvest-render-'.bin2hex(random_bytes(6)).'.php';
    file_put_contents($path, $rendered);

    try {
        expect((new PhpArrayFileReader)->read($path))->toBe($data);
    } finally {
        unlink($path);
    }
});
