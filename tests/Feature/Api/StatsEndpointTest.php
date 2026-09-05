<?php

use App\Models\Package;
use App\Models\Vendor;

it('returns aggregate package stats computed from real data, publicly', function () {
    $laravel = Vendor::factory()->composer()->create();
    $spatie = Vendor::factory()->composer()->create();

    Package::factory()->for($laravel)->create(['downloads_total' => 1_000, 'favers' => 10]);
    Package::factory()->for($laravel)->create(['downloads_total' => 2_000, 'favers' => 20]);
    Package::factory()->for($spatie)->create(['downloads_total' => 500, 'favers' => 5]);

    $response = $this->getJson('/api/stats');

    $response->assertOk()->assertExactJson([
        'packages_count' => 3,
        'vendors_count' => 2,
        'downloads_total' => 3_500,
        'favers_total' => 35,
    ]);
});
