<?php

use App\Enums\Ecosystem;
use App\Enums\PackageStatus;
use App\Services\CompatibilityChecker;

use function Pest\Laravel\freezeTime;

beforeEach(function () {
    config([
        'package_targets.php' => ['required' => ['8.4'], 'recommended' => []],
        'package_targets.laravel' => ['required' => ['12'], 'recommended' => ['13']],
    ]);
});

it('marks a package compatible when its constraints cover required and recommended versions', function () {
    $status = (new CompatibilityChecker)->determine('^8.2', ['^12.0|^13.0'], false, now());

    expect($status)->toBe(PackageStatus::Compatible);
});

it('treats a package without laravel constraints as compatible when php is covered', function () {
    $status = (new CompatibilityChecker)->determine('^8.2', [], false, now());

    expect($status)->toBe(PackageStatus::Compatible);
});

it('marks a package unclear when it covers required but misses a recommended version, even if stale', function () {
    freezeTime();

    $status = (new CompatibilityChecker)->determine('^8.2', ['^10.0|^11.0|^12.0'], false, now()->subYears(3));

    expect($status)->toBe(PackageStatus::Unclear);
});

it('requires every laravel constraint to allow the target version', function () {
    freezeTime();

    $status = (new CompatibilityChecker)->determine('^8.2', ['^12.0|^13.0', '9 - 11'], false, now()->subMonths(13));

    expect($status)->toBe(PackageStatus::Critical);
});

it('marks a package unclear when it lacks required support but released recently', function () {
    freezeTime();

    $status = (new CompatibilityChecker)->determine('^7.4', ['^10.0'], false, now()->subMonth());

    expect($status)->toBe(PackageStatus::Unclear);
});

it('marks a package critical when it lacks required support and is stale', function () {
    freezeTime();

    $status = (new CompatibilityChecker)->determine('^7.4', ['^10.0'], false, now()->subMonths(13));

    expect($status)->toBe(PackageStatus::Critical);
});

it('marks an abandoned package critical even when it covers every target version', function () {
    $status = (new CompatibilityChecker)->determine('^8.2', ['^12.0|^13.0'], true, now());

    expect($status)->toBe(PackageStatus::Critical);
});

it('marks a package critical when it lacks required support and has no known release date', function () {
    $status = (new CompatibilityChecker)->determine('^7.4', ['^10.0'], false, null);

    expect($status)->toBe(PackageStatus::Critical);
});

it('marks a package without a tagged release unclear when its default branch covers the targets', function () {
    $status = (new CompatibilityChecker)->determine('^8.2', ['^12.0|^13.0'], false, null, hasRelease: false);

    expect($status)->toBe(PackageStatus::Unclear);
});

it('marks a package without a tagged release critical when its default branch misses a required version', function () {
    $status = (new CompatibilityChecker)->determine('^8.2', ['^10.0|^11.0'], false, null, hasRelease: false);

    expect($status)->toBe(PackageStatus::Critical);
});

it('marks an old composer package without laravel constraints unclear', function () {
    freezeTime();

    $checker = new CompatibilityChecker;

    expect($checker->determine('>=5.5.9', [], false, now()->subYears(3)->subDay()))->toBe(PackageStatus::Unclear)
        ->and($checker->determine('>=5.5.9', [], false, now()->subYears(3)->addDay()))->toBe(PackageStatus::Compatible);
});

it('does not apply the missing-constraint age rule to npm packages', function () {
    freezeTime();

    $status = (new CompatibilityChecker)->determine(null, [], false, now()->subYears(5), ecosystem: Ecosystem::Npm);

    expect($status)->toBe(PackageStatus::Compatible);
});
