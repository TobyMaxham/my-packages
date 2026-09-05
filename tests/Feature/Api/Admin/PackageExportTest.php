<?php

use App\Enums\Ecosystem;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\travelTo;

it('rejects guests with a 401', function () {
    $response = $this->getJson('/api/admin/packages/export');

    $response->assertUnauthorized();
});

it('returns the full export envelope for authenticated requests', function () {
    config([
        'package_targets.php' => '8.4',
        'package_targets.laravel' => ['12', '13'],
    ]);

    Sanctum::actingAs(User::factory()->create(), ['*']);

    $response = $this->getJson('/api/admin/packages/export');

    $response->assertOk();
    expect($response->json())
        ->toHaveKeys(['generated_at', 'target_versions', 'status_legend', 'packages'])
        ->and($response->json('target_versions'))->toBe(['php' => '8.4', 'laravel' => ['12', '13']])
        ->and($response->json('status_legend'))->toBe(PackageStatus::legend());
});

it('computes days/years since last release server-side, as UTC ISO-8601 strings', function () {
    travelTo(Carbon::parse('2026-09-05T10:00:00Z'));

    $vendor = Vendor::factory()->composer()->create();
    Package::factory()->for($vendor)->create([
        'ecosystem' => Ecosystem::Composer,
        'name' => 'acme/ancient',
        'repository_url' => 'https://github.com/acme/ancient',
        'latest_version' => 'v1.0.0',
        'php_constraint' => '^5.6',
        'framework_constraint' => null,
        'last_release_at' => '2013-08-01T00:00:00Z',
        'last_checked_at' => '2026-09-01T03:00:00Z',
        'abandoned' => true,
        'status' => PackageStatus::Critical,
        'downloads_total' => 49_645,
        'favers' => 120,
    ]);

    Sanctum::actingAs(User::factory()->create(), ['*']);

    $response = $this->getJson('/api/admin/packages/export');

    $response->assertOk();

    expect($response->json('generated_at'))->toBe('2026-09-05T10:00:00Z')
        ->and($response->json('packages.0'))->toBe([
            'name' => 'acme/ancient',
            'vendor' => $vendor->name,
            'ecosystem' => 'composer',
            'repository_url' => 'https://github.com/acme/ancient',
            'latest_version' => 'v1.0.0',
            'php_constraint' => '^5.6',
            'framework_constraint' => null,
            'last_release_at' => '2013-08-01T00:00:00Z',
            'days_since_last_release' => 4783,
            'years_since_last_release' => 13.1,
            'abandoned' => true,
            'status' => 'critical',
            'downloads_total' => 49_645,
            'favers' => 120,
            'last_checked_at' => '2026-09-01T03:00:00Z',
        ]);
});

it('returns null for the release-age fields when the release date is unknown', function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);

    Package::factory()->for(Vendor::factory()->composer())->create(['last_release_at' => null]);

    $response = $this->getJson('/api/admin/packages/export');

    $response->assertOk();
    expect($response->json('packages.0.days_since_last_release'))->toBeNull()
        ->and($response->json('packages.0.years_since_last_release'))->toBeNull()
        ->and($response->json('packages.0.last_release_at'))->toBeNull();
});

it('never paginates, even with more packages than a normal page size', function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $vendor = Vendor::factory()->composer()->create();
    Package::factory()->for($vendor)->count(20)->create();

    $response = $this->getJson('/api/admin/packages/export');

    $response->assertOk();
    expect($response->json('packages'))->toHaveCount(20);
});
