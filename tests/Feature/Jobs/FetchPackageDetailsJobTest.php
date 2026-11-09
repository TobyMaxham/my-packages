<?php

use App\Enums\Ecosystem;
use App\Enums\PackageStatus;
use App\Jobs\FetchPackageDetailsJob;
use App\Models\Package;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'package_targets.php' => ['required' => ['8.4'], 'recommended' => []],
        'package_targets.laravel' => ['required' => ['12'], 'recommended' => ['13']],
    ]);
});

it('syncs composer package details from packagist', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://repo.packagist.org/p2/acme/widget~dev.json' => Http::response([], 404),
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
        'https://repo.packagist.org/p2/acme/widget.json' => Http::response([
            'minified' => 'composer/2.0',
            'packages' => [
                'acme/widget' => [
                    [
                        'version' => 'v2.0.0',
                        'description' => 'A better widget',
                        'require' => ['php' => '^8.3', 'laravel/framework' => '^12.0|^13.0'],
                        'time' => '2026-02-01T00:00:00+00:00',
                        'keywords' => ['widget', 'laravel'],
                    ],
                    [
                        'version' => 'v1.2.0',
                        'description' => 'A widget',
                        'require' => ['php' => '^8.2', 'laravel/framework' => '^11.0'],
                        'time' => '2026-01-01T00:00:00+00:00',
                        'keywords' => '__unset',
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
        ->description->toBe('A better widget')
        ->latest_version->toBe('v2.0.0')
        ->framework_constraint->toBe('^12.0|^13.0')
        ->downloads_total->toBe(100)
        ->favers->toBe(5)
        ->tags->toBe(['widget', 'laravel'])
        ->status->toBe(PackageStatus::Compatible);
});

it('falls back to the package details versions when the v2 metadata endpoint fails', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://packagist.org/packages/acme/widget.json' => Http::response([
            'package' => [
                'description' => 'A widget',
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
        'https://repo.packagist.org/*' => Http::response(['error' => 'nope'], 500),
    ]);

    $package = Package::factory()->for(Vendor::factory()->composer())->create([
        'ecosystem' => Ecosystem::Composer,
        'name' => 'acme/widget',
    ]);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh())
        ->latest_version->toBe('v1.2.0')
        ->tags->toBe(['widget']);
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

it('derives the framework constraint from every illuminate component', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://repo.packagist.org/p2/acme/mailer~dev.json' => Http::response([], 404),
        'https://packagist.org/packages/acme/mailer.json' => Http::response([
            'package' => ['description' => 'A mailer', 'versions' => []],
        ]),
        'https://repo.packagist.org/p2/acme/mailer.json' => Http::response([
            'packages' => [
                'acme/mailer' => [
                    [
                        'version' => 'v3.0',
                        'require' => [
                            'php' => '^8.2',
                            'illuminate/support' => '^12.0|^13.0',
                            'illuminate/mail' => '9 - 11',
                        ],
                        'time' => now()->subYears(2)->toIso8601String(),
                    ],
                ],
            ],
        ]),
    ]);

    $package = Package::factory()->for(Vendor::factory()->composer())->create([
        'ecosystem' => Ecosystem::Composer,
        'name' => 'acme/mailer',
    ]);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh())
        ->framework_constraint->toBe('^12.0|^13.0 & 9 - 11')
        ->status->toBe(PackageStatus::Critical);
});

it('flags dependencies on tracked packages whose constraint excludes their latest version', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://repo.packagist.org/p2/acme/identity~dev.json' => Http::response([], 404),
        'https://packagist.org/packages/acme/identity.json' => Http::response([
            'package' => ['description' => 'Identity', 'versions' => []],
        ]),
        'https://repo.packagist.org/p2/acme/identity.json' => Http::response([
            'packages' => [
                'acme/identity' => [
                    [
                        'version' => 'v1.0.0',
                        'require' => [
                            'php' => '^8.3',
                            'acme/auth' => '^2.0',
                            'acme/service' => '^2.0',
                            'vendor/untracked' => '^1.0',
                        ],
                        'time' => now()->toIso8601String(),
                    ],
                ],
            ],
        ]),
    ]);

    $vendor = Vendor::factory()->composer()->create();
    Package::factory()->for($vendor)->create(['name' => 'acme/auth', 'latest_version' => 'v3.0.1']);
    Package::factory()->for($vendor)->create(['name' => 'acme/service', 'latest_version' => 'v2.1.0']);
    $package = Package::factory()->for($vendor)->create(['name' => 'acme/identity']);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh()->outdated_dependencies)->toBe([
        ['name' => 'acme/auth', 'constraint' => '^2.0', 'latest_version' => 'v3.0.1'],
    ]);
});

it('checks the default branch constraints when a package has no tagged release', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://packagist.org/packages/acme/filesystem.json' => Http::response([
            'package' => ['description' => 'A filesystem', 'versions' => []],
        ]),
        'https://repo.packagist.org/p2/acme/filesystem.json' => Http::response([
            'packages' => ['acme/filesystem' => []],
        ]),
        'https://repo.packagist.org/p2/acme/filesystem~dev.json' => Http::response([
            'minified' => 'composer/2.0',
            'packages' => [
                'acme/filesystem' => [
                    [
                        'version' => 'dev-main',
                        'default-branch' => true,
                        'require' => ['php' => '^8.2', 'laravel/framework' => '^10.0|^11.0'],
                        'time' => now()->subMonth()->toIso8601String(),
                    ],
                ],
            ],
        ]),
    ]);

    $package = Package::factory()->for(Vendor::factory()->composer())->create([
        'name' => 'acme/filesystem',
        'latest_version' => null,
    ]);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh())
        ->latest_version->toBeNull()
        ->last_release_at->toBeNull()
        ->framework_constraint->toBe('^10.0|^11.0')
        ->status->toBe(PackageStatus::Critical);
});

it('flags constraint changes on the default branch that no release ships yet', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://packagist.org/packages/acme/props.json' => Http::response([
            'package' => ['description' => 'Props', 'versions' => []],
        ]),
        'https://repo.packagist.org/p2/acme/props.json' => Http::response([
            'packages' => [
                'acme/props' => [
                    [
                        'version' => 'v1.2.1',
                        'require' => ['php' => '^8.2', 'illuminate/support' => '^11.0|^12.0'],
                        'time' => now()->subMonth()->toIso8601String(),
                    ],
                ],
            ],
        ]),
        'https://repo.packagist.org/p2/acme/props~dev.json' => Http::response([
            'packages' => [
                'acme/props' => [
                    [
                        'version' => 'dev-main',
                        'default-branch' => true,
                        'require' => ['php' => '^8.2', 'illuminate/support' => '^12.0 || ^13.0'],
                    ],
                ],
            ],
        ]),
    ]);

    $package = Package::factory()->for(Vendor::factory()->composer())->create(['name' => 'acme/props']);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh()->unreleased_changes)->toBe([
        ['type' => 'laravel', 'release' => '^11.0|^12.0', 'default_branch' => '^12.0 || ^13.0'],
    ]);
});

it('does not flag the default branch when its constraints only differ in notation', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://packagist.org/packages/acme/props.json' => Http::response([
            'package' => ['description' => 'Props', 'versions' => []],
        ]),
        'https://repo.packagist.org/p2/acme/props.json' => Http::response([
            'packages' => [
                'acme/props' => [
                    [
                        'version' => 'v2.0.0',
                        'require' => ['php' => '^8.3', 'illuminate/support' => '^12.0|^13.0'],
                        'time' => now()->subMonth()->toIso8601String(),
                    ],
                ],
            ],
        ]),
        'https://repo.packagist.org/p2/acme/props~dev.json' => Http::response([
            'packages' => [
                'acme/props' => [
                    [
                        'version' => 'dev-main',
                        'default-branch' => true,
                        'require' => ['php' => '^8.3', 'illuminate/support' => '^12 || ^13'],
                    ],
                ],
            ],
        ]),
    ]);

    $package = Package::factory()->for(Vendor::factory()->composer())->create(['name' => 'acme/props']);

    app()->call([new FetchPackageDetailsJob($package), 'handle']);

    expect($package->fresh()->unreleased_changes)->toBe([]);
});
