<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Scanning\FileScanner;
use Random\RandomException;

/**
 * Creates a temporary directory tree for a test and returns its root path.
 *
 * @param  array<string, string>  $files  Relative path => file contents.
 *
 * @throws RandomException
 */
function makeScannerFixture(array $files): string
{
    $root = sys_get_temp_dir().'/lang-harvest-'.bin2hex(random_bytes(6));
    mkdir($root, recursive: true);

    foreach ($files as $relativePath => $contents) {
        $fullPath = $root.'/'.$relativePath;
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        file_put_contents($fullPath, $contents);
    }

    return $root;
}

function removeScannerFixture(string $root): void
{
    if (! is_dir($root)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $fileInfo) {
        $fileInfo->isDir() ? rmdir($fileInfo->getPathname()) : unlink($fileInfo->getPathname());
    }

    rmdir($root);
}

it('finds .php and .blade.php files recursively', function () {
    $root = makeScannerFixture([
        'app/Models/Car.php' => '<?php',
        'resources/views/welcome.blade.php' => '<div></div>',
        'resources/views/readme.md' => '# readme',
    ]);

    try {
        $files = (new FileScanner)->scan([$root]);

        expect($files)->toBe([
            $root.'/app/Models/Car.php',
            $root.'/resources/views/welcome.blade.php',
        ]);
    } finally {
        removeScannerFixture($root);
    }
});

it('ignores non-php files', function () {
    $root = makeScannerFixture([
        'notes.txt' => 'not php',
        'config.json' => '{}',
        'App.php' => '<?php',
    ]);

    try {
        $files = (new FileScanner)->scan([$root]);

        expect($files)->toBe([$root.'/App.php']);
    } finally {
        removeScannerFixture($root);
    }
});

it('does not descend into an excluded directory', function () {
    $root = makeScannerFixture([
        'app/Models/Car.php' => '<?php',
        'vendor/some-package/File.php' => '<?php',
    ]);

    try {
        $files = (new FileScanner)->scan([$root], [$root.'/vendor']);

        expect($files)->toBe([$root.'/app/Models/Car.php']);
    } finally {
        removeScannerFixture($root);
    }
});

it('does not exclude a sibling directory with a similar name prefix', function () {
    $root = makeScannerFixture([
        'storage/Cache.php' => '<?php',
        'storage-backup/Old.php' => '<?php',
    ]);

    try {
        $files = (new FileScanner)->scan([$root], [$root.'/storage']);

        expect($files)->toBe([$root.'/storage-backup/Old.php']);
    } finally {
        removeScannerFixture($root);
    }
});

it('deduplicates files reached through overlapping paths', function () {
    $root = makeScannerFixture([
        'app/Models/Car.php' => '<?php',
    ]);

    try {
        $files = (new FileScanner)->scan([$root, $root.'/app']);

        expect($files)->toBe([$root.'/app/Models/Car.php']);
    } finally {
        removeScannerFixture($root);
    }
});

it('accepts a direct file path instead of a directory', function () {
    $root = makeScannerFixture([
        'app/Models/Car.php' => '<?php',
    ]);

    try {
        $files = (new FileScanner)->scan([$root.'/app/Models/Car.php']);

        expect($files)->toBe([$root.'/app/Models/Car.php']);
    } finally {
        removeScannerFixture($root);
    }
});

it('silently skips a path that does not exist', function () {
    $files = (new FileScanner)->scan([sys_get_temp_dir().'/lang-harvest-missing-'.bin2hex(random_bytes(6))]);

    expect($files)->toBe([]);
});
