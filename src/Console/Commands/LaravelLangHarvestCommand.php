<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Console\Commands;

use Illuminate\Console\Command;

class LaravelLangHarvestCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'laravel-lang-harvest:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package laravel-lang-harvest.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('LaravelLangHarvest placeholder command executed.');

        return self::SUCCESS;
    }
}
