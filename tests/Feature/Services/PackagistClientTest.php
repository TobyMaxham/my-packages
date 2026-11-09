<?php

use App\Services\PackagistClient;

it('picks the highest stable version when tags mix "v" prefixed and unprefixed names', function () {
    $latest = (new PackagistClient)->latestVersion([
        'dev-main' => ['version' => 'dev-main'],
        'v2.0.2' => ['version' => 'v2.0.2'],
        'v2.0.1' => ['version' => 'v2.0.1'],
        'v1.3' => ['version' => 'v1.3'],
        '1.1' => ['version' => '1.1'],
        'v1.0.1' => ['version' => 'v1.0.1'],
    ]);

    expect($latest['version'])->toBe('v2.0.2');
});
