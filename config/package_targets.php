<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Target Versions
    |--------------------------------------------------------------------------
    |
    | The versions every tracked package is checked against. "php" is a
    | single version constraint target; "laravel" is a list of Laravel
    | major versions, a package is considered compatible if its
    | constraint covers at least one of them.
    |
    */

    'php' => env('TARGET_PHP_VERSION', '8.4'),

    'laravel' => explode(',', (string) env('TARGET_LARAVEL_VERSIONS', '12,13')),

];
