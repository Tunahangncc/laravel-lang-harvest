<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    |
    | The locale that harvested keys and messages are written under:
    | lang/{locale}/{group}.php and lang/{locale}.json. Leave this null to
    | fall back to the application's own locale (config('app.locale')) at
    | run time.
    |
    */
    'locale' => env('LANG_HARVEST_LOCALE'),

    /*
    |--------------------------------------------------------------------------
    | Scan paths
    |--------------------------------------------------------------------------
    |
    | Directories (or individual files) to scan for translation calls,
    | relative to the application's base path. Absolute paths are also
    | accepted as-is.
    |
    */
    'paths' => [
        'app',
        'resources/views',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded directories
    |--------------------------------------------------------------------------
    |
    | Directories that are never descended into, relative to the
    | application's base path. Absolute paths are also accepted as-is.
    |
    */
    'exclude' => [
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        'tests',
    ],

    /*
    |--------------------------------------------------------------------------
    | Placeholder format
    |--------------------------------------------------------------------------
    |
    | The value written for a newly harvested group key (e.g.
    | "arac.lastik-gecmisi") while it still awaits a real translation.
    | "%s" is replaced with the full dotted key.
    |
    */
    'placeholder' => '[%s]',

];
