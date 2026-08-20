<?php

declare(strict_types=1);

use LaravelLangHarvest\LaravelLangHarvest\Config\HarvestConfig;
use LaravelLangHarvest\LaravelLangHarvest\Harvesting\Harvester;
use LaravelLangHarvest\LaravelLangHarvest\Writing\PhpArrayFileReader;

/**
 * @param  array<string, string>  $sourceFiles  Relative path (under a "src" subdir) => file contents.
 * @return array{root: string, source: string, lang: string} Fixture directories.
 */
function makeHarvesterFixture(array $sourceFiles): array
{
    $root = sys_get_temp_dir().'/lang-harvest-e2e-'.bin2hex(random_bytes(6));
    $source = $root.'/src';
    $lang = $root.'/lang';
    mkdir($source, recursive: true);
    mkdir($lang, recursive: true);

    foreach ($sourceFiles as $relativePath => $contents) {
        $fullPath = $source.'/'.$relativePath;
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        file_put_contents($fullPath, $contents);
    }

    return ['root' => $root, 'source' => $source, 'lang' => $lang];
}

function removeHarvesterFixture(string $root): void
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

it('finds group keys and json texts across php and blade files and reports them without writing', function () {
    $fixture = makeHarvesterFixture([
        'Controller.php' => "<?php\n\nclass Controller {\n    public function show() {\n        return __('arac.lastik-gecmisi');\n    }\n}\n",
        'views/welcome.blade.php' => "<div>{{ trans('profile.edit.title') }}</div>\n<p>{{ __('Merhaba dunya') }}</p>",
    ]);

    try {
        $config = HarvestConfig::fromArray($fixture['root'], ['paths' => [$fixture['source']]], 'en');

        $report = (new Harvester)->run($config, $fixture['lang']);

        expect($report->scannedFileCount)->toBe(2)
            ->and($report->addedGroupEntries())->toHaveCount(2)
            ->and($report->jsonResult?->added)->toBe(['Merhaba dunya'])
            ->and($report->hasPendingChanges())->toBeTrue()
            ->and($report->hasIssues())->toBeFalse()
            ->and(is_dir($fixture['lang'].'/en'))->toBeFalse()
            ->and(is_file($fixture['lang'].'/en.json'))->toBeFalse();

        // run() must never touch disk on its own.
    } finally {
        removeHarvesterFixture($fixture['root']);
    }
});

it('writes the harvested keys to the correct lang files', function () {
    $fixture = makeHarvesterFixture([
        'Controller.php' => "<?php\n\nreturn __('arac.lastik-gecmisi');\n",
        'views/welcome.blade.php' => "<p>{{ __('Merhaba dunya') }}</p>",
    ]);

    try {
        $config = HarvestConfig::fromArray($fixture['root'], ['paths' => [$fixture['source']]], 'en');

        $harvester = new Harvester;
        $report = $harvester->run($config, $fixture['lang']);
        $harvester->write($report, $fixture['lang'], $config->locale());

        $groupData = (new PhpArrayFileReader)->read($fixture['lang'].'/en/arac.php');
        expect($groupData)->toBe(['lastik-gecmisi' => '[arac.lastik-gecmisi]']);

        $jsonData = json_decode(file_get_contents($fixture['lang'].'/en.json'), true);
        expect($jsonData)->toBe(['Merhaba dunya' => 'Merhaba dunya']);
    } finally {
        removeHarvesterFixture($fixture['root']);
    }
});

it('does not overwrite an existing translated value', function () {
    $fixture = makeHarvesterFixture([
        'Controller.php' => "<?php\n\nreturn __('arac.lastik-gecmisi');\n",
    ]);

    mkdir($fixture['lang'].'/en', recursive: true);
    file_put_contents(
        $fixture['lang'].'/en/arac.php',
        "<?php\n\nreturn [\n    'lastik-gecmisi' => 'Lastik Gecmisi',\n];\n",
    );

    try {
        $config = HarvestConfig::fromArray($fixture['root'], ['paths' => [$fixture['source']]], 'en');

        $harvester = new Harvester;
        $report = $harvester->run($config, $fixture['lang']);

        expect($report->hasPendingChanges())->toBeFalse()
            ->and($report->addedGroupEntries())->toBeEmpty();

        $harvester->write($report, $fixture['lang'], $config->locale());

        $groupData = (new PhpArrayFileReader)->read($fixture['lang'].'/en/arac.php');
        expect($groupData)->toBe(['lastik-gecmisi' => 'Lastik Gecmisi']);
    } finally {
        removeHarvesterFixture($fixture['root']);
    }
});

it('reports dynamic calls without harvesting them', function () {
    $fixture = makeHarvesterFixture([
        'Controller.php' => "<?php\n\nreturn __(\$message);\n",
    ]);

    try {
        $config = HarvestConfig::fromArray($fixture['root'], ['paths' => [$fixture['source']]], 'en');

        $report = (new Harvester)->run($config, $fixture['lang']);

        expect($report->dynamicCalls)->toHaveCount(1)
            ->and($report->dynamicCalls[0]->callee)->toBe('__')
            ->and($report->hasIssues())->toBeTrue()
            ->and($report->hasPendingChanges())->toBeFalse();
    } finally {
        removeHarvesterFixture($fixture['root']);
    }
});

it('reports a conflict when a group path segment already exists as a non-array value', function () {
    $fixture = makeHarvesterFixture([
        'Controller.php' => "<?php\n\nreturn __('profile.edit.title');\n",
    ]);

    mkdir($fixture['lang'].'/en', recursive: true);
    file_put_contents(
        $fixture['lang'].'/en/profile.php',
        "<?php\n\nreturn [\n    'edit' => 'Duzenle',\n];\n",
    );

    try {
        $config = HarvestConfig::fromArray($fixture['root'], ['paths' => [$fixture['source']]], 'en');

        $report = (new Harvester)->run($config, $fixture['lang']);

        expect($report->hasConflicts())->toBeTrue()
            ->and($report->conflictingGroupEntries())->toHaveCount(1)
            ->and($report->conflictingGroupEntries()[0]->rawKey)->toBe('profile.edit.title');
    } finally {
        removeHarvesterFixture($fixture['root']);
    }
});

it('does not descend into excluded directories', function () {
    $fixture = makeHarvesterFixture([
        'Controller.php' => "<?php\n\nreturn __('arac.lastik-gecmisi');\n",
        'vendor/SomePackage.php' => "<?php\n\nreturn __('vendor.should-not-be-harvested');\n",
    ]);

    try {
        $config = HarvestConfig::fromArray($fixture['root'], [
            'paths' => [$fixture['source']],
            'exclude' => [$fixture['source'].'/vendor'],
        ], 'en');

        $report = (new Harvester)->run($config, $fixture['lang']);

        expect($report->scannedFileCount)->toBe(1)
            ->and($report->addedGroupEntries())->toHaveCount(1)
            ->and($report->addedGroupEntries()[0]->rawKey)->toBe('arac.lastik-gecmisi');
    } finally {
        removeHarvesterFixture($fixture['root']);
    }
});
