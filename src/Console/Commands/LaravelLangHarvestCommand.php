<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Console\Commands;

use Illuminate\Console\Command;
use LaravelLangHarvest\LaravelLangHarvest\Config\HarvestConfig;
use LaravelLangHarvest\LaravelLangHarvest\Harvesting\Harvester;
use LaravelLangHarvest\LaravelLangHarvest\Harvesting\HarvestReport;

final class LaravelLangHarvestCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'lang:harvest
        {--dry-run : Show what would be added without writing any files}
        {--check : Do not write any files; exit with a non-zero status if anything is missing or needs review}';

    /**
     * The command description.
     */
    protected $description = 'Scan the codebase for translation calls and add missing keys to the lang files.';

    public function handle(Harvester $harvester): int
    {
        $isCheck = (bool) $this->option('check');
        $isDryRun = $isCheck || (bool) $this->option('dry-run');

        $config = HarvestConfig::fromArray(
            base_path(),
            (array) config('laravel-lang-harvest', []),
            app()->getLocale(),
        );

        $report = $harvester->run($config, lang_path());

        if (! $isDryRun) {
            $harvester->write($report, lang_path(), $config->locale());
        }

        $this->printReport($report, $isDryRun);

        if ($isCheck && ($report->hasPendingChanges() || $report->hasIssues())) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function printReport(HarvestReport $report, bool $isDryRun): void
    {
        $this->line("Taranan dosya sayısı: {$report->scannedFileCount}");

        $addedGroupEntries = $report->addedGroupEntries();
        $addedJsonTexts = $report->jsonResult === null ? [] : $report->jsonResult->added;

        if ($addedGroupEntries === [] && $addedJsonTexts === []) {
            $this->info('Eksik key bulunamadı, her şey güncel.');
        } else {
            $verb = $isDryRun ? 'eklenecek' : 'eklendi';

            if ($addedGroupEntries !== []) {
                $this->line("\n<info>Group key'ler ({$verb}):</info>");

                foreach ($addedGroupEntries as $entry) {
                    $this->line("  {$entry->rawKey} => {$entry->placeholder}");
                }
            }

            if ($addedJsonTexts !== []) {
                $this->line("\n<info>JSON metinleri ({$verb}):</info>");

                foreach ($addedJsonTexts as $text) {
                    $this->line("  {$text}");
                }
            }
        }

        if ($report->dynamicCalls !== []) {
            $this->line("\n<comment>Dinamik çağrılar (manuel kontrol gerekiyor):</comment>");

            foreach ($report->dynamicCalls as $call) {
                $this->line("  {$call->file}:{$call->line} ({$call->callee})");
            }
        }

        $conflicts = $report->conflictingGroupEntries();

        if ($conflicts !== []) {
            $this->line("\n<error>Çakışmalar (elle çözülmeli):</error>");

            foreach ($conflicts as $entry) {
                $this->line("  {$entry->rawKey}");
            }
        }

        if ($report->unreadableFiles !== []) {
            $this->line("\n<error>Okunamayan dosyalar:</error>");

            foreach ($report->unreadableFiles as $notice) {
                $this->line("  {$notice->path}: {$notice->reason}");
            }
        }
    }
}
