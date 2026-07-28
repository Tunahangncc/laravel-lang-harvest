<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest;

use Illuminate\Support\ServiceProvider;
use LaravelLangHarvest\LaravelLangHarvest\Console\Commands\LaravelLangHarvestCommand;

class LaravelLangHarvestServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-lang-harvest.php', 'laravel-lang-harvest');

        $this->app->singleton(LaravelLangHarvest::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-lang-harvest.php' => config_path('laravel-lang-harvest.php'),
        ], ['laravel-lang-harvest', 'laravel-lang-harvest-config']);

        $this->commands([
            LaravelLangHarvestCommand::class,
        ]);
    }
}
