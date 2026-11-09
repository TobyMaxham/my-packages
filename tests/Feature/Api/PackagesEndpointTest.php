<?php

use App\Enums\Ecosystem;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use App\Models\Vendor;
use Laravel\Sanctum\Sanctum;

it('returns only neutral, non-evaluative fields for guests', function () {
    $vendor = Vendor::factory()->composer()->create(['name' => 'laravel']);
    Package::factory()->for($vendor)->create([
        'ecosystem' => Ecosystem::Composer,
        'name' => 'laravel/framework',
    ]);

    $response = $this->getJson('/api/packages');

    $response->assertOk();

    expect($response->json('data.0'))
        ->toHaveKeys([
            'id', 'name', 'vendor', 'ecosystem', 'tags', 'description', 'github_url',
            'latest_version', 'last_release_at', 'php_range', 'laravel_range',
            'is_featured', 'featured_note',
        ])
        ->not->toHaveKeys(['status', 'abandoned', 'downloads_total', 'favers', 'last_checked_at', 'outdated_dependencies']);
});

it('filters to only featured packages, publicly', function () {
    $vendor = Vendor::factory()->composer()->create();
    Package::factory()->for($vendor)->featured()->create(['name' => 'a/featured']);
    Package::factory()->for($vendor)->create(['name' => 'a/plain']);

    $response = $this->getJson('/api/packages?featured=1');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.name'))->toBe('a/featured')
        ->and($response->json('data.0.is_featured'))->toBeTrue()
        ->and($response->json('data.0.featured_note'))->not->toBeNull();
});

it('includes the computed status and extended fields only for authenticated requests', function () {
    $vendor = Vendor::factory()->composer()->create(['name' => 'laravel']);
    Package::factory()->for($vendor)->create([
        'ecosystem' => Ecosystem::Composer,
        'name' => 'laravel/framework',
        'status' => PackageStatus::Critical,
        'downloads_total' => 12_345,
        'favers' => 42,
    ]);

    Sanctum::actingAs(User::factory()->create(), ['*']);

    $response = $this->getJson('/api/packages');

    $response->assertOk();

    expect($response->json('data.0'))
        ->toHaveKeys(['status', 'downloads_total', 'favers', 'abandoned', 'last_checked_at', 'outdated_dependencies'])
        ->and($response->json('data.0.status'))->toBe('critical')
        ->and($response->json('data.0.downloads_total'))->toBe(12_345)
        ->and($response->json('data.0.favers'))->toBe(42);
});

it('filters packages by status only for authenticated requests', function () {
    $vendor = Vendor::factory()->composer()->create();
    Package::factory()->for($vendor)->create(['status' => PackageStatus::Compatible, 'name' => 'a/compatible']);
    Package::factory()->for($vendor)->create(['status' => PackageStatus::Critical, 'name' => 'a/critical']);

    Sanctum::actingAs(User::factory()->create(), ['*']);

    $response = $this->getJson('/api/packages?status=critical');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.name'))->toBe('a/critical');
});

it('ignores the status filter for guests', function () {
    $vendor = Vendor::factory()->composer()->create();
    Package::factory()->for($vendor)->create(['status' => PackageStatus::Compatible, 'name' => 'a/compatible']);
    Package::factory()->for($vendor)->create(['status' => PackageStatus::Critical, 'name' => 'a/critical']);

    $response = $this->getJson('/api/packages?status=critical');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('filters packages by vendor name', function () {
    $laravel = Vendor::factory()->composer()->create(['name' => 'laravel']);
    $spatie = Vendor::factory()->composer()->create(['name' => 'spatie']);
    Package::factory()->for($laravel)->create(['name' => 'laravel/framework']);
    Package::factory()->for($spatie)->create(['name' => 'spatie/laravel-permission']);

    $response = $this->getJson('/api/packages?vendor=spatie');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.vendor'))->toBe('spatie');
});

it('sorts packages by most recently released first, with unknown releases last', function () {
    $vendor = Vendor::factory()->composer()->create();
    Package::factory()->for($vendor)->create(['name' => 'a/older', 'last_release_at' => now()->subYear()]);
    Package::factory()->for($vendor)->create(['name' => 'a/newest', 'last_release_at' => now()->subDay()]);
    Package::factory()->for($vendor)->create(['name' => 'a/unknown', 'last_release_at' => null]);

    $response = $this->getJson('/api/packages');

    $response->assertOk();
    expect($response->json('data.*.name'))->toBe(['a/newest', 'a/older', 'a/unknown']);
});

it('returns tracked vendor names as a plain string list', function () {
    Vendor::factory()->composer()->create(['name' => 'laravel']);
    Vendor::factory()->composer()->create(['name' => 'spatie']);

    $response = $this->getJson('/api/vendors');

    $response->assertOk();
    expect($response->json('data'))->toBe(['laravel', 'spatie']);
});
