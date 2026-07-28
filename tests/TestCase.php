<?php

declare(strict_types=1);

namespace LaravelLangHarvest\LaravelLangHarvest\Tests;

use LaravelLangHarvest\LaravelLangHarvest\LaravelLangHarvestServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelLangHarvestServiceProvider::class,
        ];
    }
}
