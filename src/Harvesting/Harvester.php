<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Harvesting;

use LaravelLangHarvest\LaravelLangHarvest\Config\HarvestConfig;
use LaravelLangHarvest\LaravelLangHarvest\Extraction\BladeFileExtractor;
use LaravelLangHarvest\LaravelLangHarvest\Extraction\PhpFileExtractor;
use LaravelLangHarvest\LaravelLangHarvest\Keys\KeyClassifier;
use LaravelLangHarvest\LaravelLangHarvest\Keys\TranslationKeyType;
use LaravelLangHarvest\LaravelLangHarvest\Scanning\FileScanner;
use LaravelLangHarvest\LaravelLangHarvest\Writing\GroupFileMerger;
use LaravelLangHarvest\LaravelLangHarvest\Writing\GroupKeyEntry;
use LaravelLangHarvest\LaravelLangHarvest\Writing\GroupMergeResult;
use LaravelLangHarvest\LaravelLangHarvest\Writing\JsonLangFileWriter;
use LaravelLangHarvest\LaravelLangHarvest\Writing\JsonMergeResult;
use LaravelLangHarvest\LaravelLangHarvest\Writing\PhpArrayFileReader;
use LaravelLangHarvest\LaravelLangHarvest\Writing\PhpArrayFileRenderer;
use LaravelLangHarvest\LaravelLangHarvest\Writing\UnreadableLangFileException;
use PhpParser\Error as PhpParserError;

/**
 * Scans configured paths for translation calls, classifies each static
 * key, and computes (via run()) or applies (via write()) the resulting
 * additions to the lang files -- without ever overwriting an existing
 * value.
 *
 * run() never touches disk beyond reading source and existing lang
 * files, which is what makes --dry-run and --check possible: callers
 * can inspect the report and decide whether to call write() at all.
 */
final readonly class Harvester
{
    public function __construct(
        private FileScanner $scanner = new FileScanner,
        private PhpFileExtractor $phpExtractor = new PhpFileExtractor,
        private BladeFileExtractor $bladeExtractor = new BladeFileExtractor,
        private KeyClassifier $keyClassifier = new KeyClassifier,
        private PhpArrayFileReader $groupReader = new PhpArrayFileReader,
        private GroupFileMerger $groupMerger = new GroupFileMerger,
        private JsonLangFileWriter $jsonWriter = new JsonLangFileWriter,
        private PhpArrayFileRenderer $groupRenderer = new PhpArrayFileRenderer,
    ) {}

    public function run(HarvestConfig $config, string $langDirectory): HarvestReport
    {
        $files = $this->scanner->scan($config->scanPaths(), $config->excludedDirectories());

        /** @var array<string, array<string, GroupKeyEntry>> $groupEntriesByGroup */
        $groupEntriesByGroup = [];
        /** @var array<string, string> $jsonTexts */
        $jsonTexts = [];
        /** @var array<int, DynamicCallSite> $dynamicCalls */
        $dynamicCalls = [];
        /** @var array<int, UnreadableFileNotice> $unreadable */
        $unreadable = [];

        foreach ($files as $file) {
            $this->processFile(
                $file,
                $config,
                $groupEntriesByGroup,
                $jsonTexts,
                $dynamicCalls,
                $unreadable,
            );
        }

        $groupResults = $this->mergeGroups($groupEntriesByGroup, $langDirectory, $config->locale(), $unreadable);
        $jsonResult = $this->mergeJson($jsonTexts, $langDirectory, $config->locale(), $unreadable);

        return new HarvestReport(
            scannedFileCount: count($files),
            groupResults: $groupResults,
            jsonResult: $jsonResult,
            dynamicCalls: $dynamicCalls,
            unreadableFiles: $unreadable,
        );
    }

    public function write(HarvestReport $report, string $langDirectory, string $locale): void
    {
        foreach ($report->groupResults as $group => $result) {
            if (! $result->hasChanges()) {
                continue;
            }

            $path = $this->groupFilePath($langDirectory, $locale, $group);
            $this->ensureDirectoryExists(dirname($path));

            file_put_contents($path, $this->groupRenderer->render($result->data));
        }

        if ($report->jsonResult !== null && $report->jsonResult->hasChanges()) {
            $path = $this->jsonFilePath($langDirectory, $locale);
            $this->ensureDirectoryExists(dirname($path));

            $this->jsonWriter->write($path, $report->jsonResult->data);
        }
    }

    /**
     * @param  array<string, array<string, GroupKeyEntry>>  $groupEntriesByGroup
     * @param  array<string, string>  $jsonTexts
     * @param  array<int, DynamicCallSite>  $dynamicCalls
     * @param  array<int, UnreadableFileNotice>  $unreadable
     */
    private function processFile(
        string $file,
        HarvestConfig $config,
        array &$groupEntriesByGroup,
        array &$jsonTexts,
        array &$dynamicCalls,
        array &$unreadable,
    ): void {
        $code = file_get_contents($file);

        if ($code === false) {
            $unreadable[] = new UnreadableFileNotice($file, 'Could not read file.');

            return;
        }

        try {
            $calls = str_ends_with($file, '.blade.php')
                ? $this->bladeExtractor->extract($code)
                : $this->phpExtractor->extract($code);
        } catch (PhpParserError $error) {
            $unreadable[] = new UnreadableFileNotice($file, $error->getMessage());

            return;
        }

        foreach ($calls as $call) {
            $value = $call->argument->value;

            if (! $call->argument->isStatic || ! is_string($value)) {
                $dynamicCalls[] = new DynamicCallSite($file, $call->line, $call->callee);

                continue;
            }

            $classified = $this->keyClassifier->classify($value);

            if ($classified->type === TranslationKeyType::Group) {
                $groupEntriesByGroup[$classified->group][$classified->rawValue] ??= new GroupKeyEntry(
                    $classified->rawValue,
                    $classified->groupSegments,
                    $config->placeholder($classified->rawValue),
                );
            } else {
                $jsonTexts[$classified->rawValue] = $classified->rawValue;
            }
        }
    }

    /**
     * @param  array<string, array<string, GroupKeyEntry>>  $groupEntriesByGroup
     * @param  array<int, UnreadableFileNotice>  $unreadable
     * @return array<string, GroupMergeResult>
     */
    private function mergeGroups(
        array $groupEntriesByGroup,
        string $langDirectory,
        string $locale,
        array &$unreadable,
    ): array {
        $groupResults = [];

        foreach ($groupEntriesByGroup as $group => $entries) {
            $path = $this->groupFilePath($langDirectory, $locale, $group);

            try {
                $existing = $this->groupReader->read($path);
            } catch (UnreadableLangFileException $exception) {
                $unreadable[] = new UnreadableFileNotice($path, $exception->getMessage());

                continue;
            }

            $groupResults[$group] = $this->groupMerger->merge($existing, array_values($entries));
        }

        return $groupResults;
    }

    /**
     * @param  array<string, string>  $jsonTexts
     * @param  array<int, UnreadableFileNotice>  $unreadable
     */
    private function mergeJson(
        array $jsonTexts,
        string $langDirectory,
        string $locale,
        array &$unreadable,
    ): ?JsonMergeResult {
        $path = $this->jsonFilePath($langDirectory, $locale);

        try {
            return $this->jsonWriter->merge($path, array_values($jsonTexts));
        } catch (UnreadableLangFileException $exception) {
            $unreadable[] = new UnreadableFileNotice($path, $exception->getMessage());

            return null;
        }
    }

    private function groupFilePath(string $langDirectory, string $locale, string $group): string
    {
        return rtrim($langDirectory, '/\\')."/{$locale}/{$group}.php";
    }

    private function jsonFilePath(string $langDirectory, string $locale): string
    {
        return rtrim($langDirectory, '/\\')."/{$locale}.json";
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }
    }
}
