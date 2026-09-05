<?php

use App\Enums\PackageStatus;
use App\Services\CompatibilityChecker;

use function Pest\Laravel\freezeTime;

beforeEach(function () {
    config([
        'package_targets.php' => '8.4',
        'package_targets.laravel' => ['12', '13'],
    ]);
});

it('marks a package compatible when its constraints cover the target versions', function () {
    $status = (new CompatibilityChecker)->determine('^8.2', '^11.0|^12.0', false, now());

    expect($status)->toBe(PackageStatus::Compatible);
});

it('treats a missing framework constraint as compatible when php is covered', function () {
    $status = (new CompatibilityChecker)->determine('^8.2', null, false, now());

    expect($status)->toBe(PackageStatus::Compatible);
});

it('marks a package unclear when it lacks target support but released recently', function () {
    freezeTime();

    $status = (new CompatibilityChecker)->determine('^7.4', '^10.0', false, now()->subMonth());

    expect($status)->toBe(PackageStatus::Unclear);
});

it('marks a package critical when it lacks target support and is stale', function () {
    freezeTime();

    $status = (new CompatibilityChecker)->determine('^7.4', '^10.0', false, now()->subMonths(13));

    expect($status)->toBe(PackageStatus::Critical);
});

it('marks an abandoned package critical even with a recent release', function () {
    freezeTime();

    $status = (new CompatibilityChecker)->determine('^7.4', '^10.0', true, now()->subDay());

    expect($status)->toBe(PackageStatus::Critical);
});

it('marks a package critical when it lacks target support and has no known release date', function () {
    $status = (new CompatibilityChecker)->determine('^7.4', '^10.0', false, null);

    expect($status)->toBe(PackageStatus::Critical);
});
