<?php

use App\Enums\Ecosystem;
use App\Enums\PackageStatus;
use App\Jobs\FetchPackageDetailsJob;
use App\Models\Package;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'package_targets.php' => '8.4',
        'package_targets.laravel' => ['12', '13'],
    ]);
});

it('syncs composer package details from packagist', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://packagist.org/packages/acme/widget.json' => Http::response([
            'package' => [
                'description' => 'A widget',
                'repository' => 'https://github.com/acme/widget',
                'abandoned' => false,
                'downloads' => ['total' => 100],
                'favers' => 5,
                'versions' => [
                    'v1.2.0' => [
                        'version' => 'v1.2.0',
                        'require' => ['php' => '^8.2', 'laravel/framework' => '^11.0'],
                        'time' => '2026-01-01T00:00:00+00:00',
                        'keywords' => ['widget'],
                    ],
                ],
            ],
        ]),
    ]);

    $package = Package::factory()->for(Vendor::factory()->composer())->create([
        'ecosystem' => Ecosystem::Composer,
        'name' => 'acme/widget',
    ]);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh())
        ->description->toBe('A widget')
        ->latest_version->toBe('v1.2.0')
        ->downloads_total->toBe(100)
        ->favers->toBe(5)
        ->tags->toBe(['widget'])
        ->status->toBe(PackageStatus::Unclear);
});

it('syncs npm package details from the registry with no vendor required', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://registry.npmjs.org/*' => Http::response([
            'description' => 'Utility belt',
            'dist-tags' => ['latest' => '3.0.0'],
            'time' => ['3.0.0' => '2026-02-01T00:00:00.000Z'],
            'versions' => [
                '3.0.0' => [
                    'keywords' => ['utils'],
                    'repository' => ['url' => 'git+https://github.com/acme/utils.git'],
                ],
            ],
        ]),
    ]);

    $package = Package::factory()->create([
        'vendor_id' => null,
        'ecosystem' => Ecosystem::Npm,
        'name' => 'left-pad',
    ]);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh())
        ->description->toBe('Utility belt')
        ->latest_version->toBe('3.0.0')
        ->repository_url->toBe('https://github.com/acme/utils')
        ->tags->toBe(['utils'])
        ->php_constraint->toBeNull()
        ->framework_constraint->toBeNull();
});

it('does not crash when the npm registry rejects the request with an unexpected status', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://registry.npmjs.org/*' => Http::response(
            ['code' => 'MethodNotAllowedError', 'message' => 'GET is not allowed'],
            405,
        ),
    ]);

    $package = Package::factory()->create([
        'vendor_id' => null,
        'ecosystem' => Ecosystem::Npm,
        'name' => '@scope/broken',
        'description' => 'unchanged',
    ]);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh()->description)->toBe('unchanged');
});

it('does not crash when packagist rejects the request with an unexpected status', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://packagist.org/*' => Http::response(['error' => 'nope'], 500),
    ]);

    $package = Package::factory()->for(Vendor::factory()->composer())->create([
        'ecosystem' => Ecosystem::Composer,
        'name' => 'acme/broken',
        'description' => 'unchanged',
    ]);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh()->description)->toBe('unchanged');
});

it('marks a deprecated npm package as abandoned', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://registry.npmjs.org/*' => Http::response([
            'dist-tags' => ['latest' => '1.0.0'],
            'time' => ['1.0.0' => now()->subMonth()->toIso8601String()],
            'versions' => [
                '1.0.0' => ['deprecated' => 'Use something-else instead'],
            ],
        ]),
    ]);

    $package = Package::factory()->create([
        'vendor_id' => null,
        'ecosystem' => Ecosystem::Npm,
        'name' => '@scope/old-package',
    ]);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh()->abandoned)->toBeTrue();
});
