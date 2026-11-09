<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Target Versions
    |--------------------------------------------------------------------------
    |
    | The PHP and Laravel versions every tracked package is checked against.
    |
    | "required": versions the latest release MUST allow. Missing one makes
    | the package "unclear" (recent release) or "critical" (stale).
    |
    | "recommended": versions the latest release SHOULD allow. Missing one
    | only downgrades an otherwise compatible package to "unclear".
    |
    | Values are comma-separated in the .env file; leave a key empty to
    | skip that check. Changes apply on the next scan.
    |
    */

    'php' => [
        'required' => array_values(array_filter(array_map('trim', explode(',', (string) env('TARGET_PHP_REQUIRED', '8.4'))))),
        'recommended' => array_values(array_filter(array_map('trim', explode(',', (string) env('TARGET_PHP_RECOMMENDED', ''))))),
    ],

    'laravel' => [
        'required' => array_values(array_filter(array_map('trim', explode(',', (string) env('TARGET_LARAVEL_REQUIRED', '12'))))),
        'recommended' => array_values(array_filter(array_map('trim', explode(',', (string) env('TARGET_LARAVEL_RECOMMENDED', '13'))))),
    ],

];
