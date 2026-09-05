<?php

use App\Jobs\FetchPackageDetailsJob;
use App\Models\Package;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('creates the vendor and its packages, then queues a check for each', function () {
    Queue::fake([FetchPackageDetailsJob::class]);
    Http::preventStrayRequests();
    Http::fake([
        'https://packagist.org/packages/list.json*' => Http::response([
            'packageNames' => ['acme/one', 'acme/two'],
        ]),
    ]);

    $this->artisan('packages:scan-vendor', ['vendor' => 'acme'])->assertExitCode(0);

    expect(Vendor::query()->where('name', 'acme')->exists())->toBeTrue()
        ->and(Package::query()->whereIn('name', ['acme/one', 'acme/two'])->count())->toBe(2);

    Queue::assertPushed(FetchPackageDetailsJob::class, 2);
});

it('does not crash and creates nothing when packagist is unavailable', function () {
    Queue::fake([FetchPackageDetailsJob::class]);
    Http::preventStrayRequests();
    Http::fake([
        'https://packagist.org/packages/list.json*' => Http::response(['error' => 'nope'], 500),
    ]);

    $this->artisan('packages:scan-vendor', ['vendor' => 'acme'])->assertExitCode(0);

    expect(Vendor::query()->count())->toBe(0)
        ->and(Package::query()->count())->toBe(0);

    Queue::assertNotPushed(FetchPackageDetailsJob::class);
});

it('reports npm scanning as not implemented without touching the database', function () {
    $this->artisan('packages:scan-vendor', ['vendor' => 'acme', 'ecosystem' => 'npm'])
        ->assertExitCode(0);

    expect(Vendor::query()->count())->toBe(0);
});
