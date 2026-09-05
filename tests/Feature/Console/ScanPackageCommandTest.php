<?php

use App\Enums\Ecosystem;
use App\Jobs\FetchPackageDetailsJob;
use App\Models\Package;
use Illuminate\Support\Facades\Queue;

it('tracks a standalone composer package without a vendor and queues a check', function () {
    Queue::fake([FetchPackageDetailsJob::class]);

    $this->artisan('packages:scan-package', ['package' => 'acme/widget'])
        ->assertExitCode(0);

    $package = Package::query()->where('name', 'acme/widget')->sole();

    expect($package->vendor_id)->toBeNull()
        ->and($package->ecosystem)->toBe(Ecosystem::Composer);

    Queue::assertPushed(FetchPackageDetailsJob::class);
});

it('rejects a composer package name that is not "vendor/package"', function () {
    $this->artisan('packages:scan-package', ['package' => 'no-slash-here'])
        ->assertExitCode(1);

    expect(Package::query()->count())->toBe(0);
});

it('tracks a standalone npm package, scoped or unscoped, without a vendor', function (string $name) {
    Queue::fake([FetchPackageDetailsJob::class]);

    $this->artisan('packages:scan-package', ['package' => $name, 'ecosystem' => 'npm'])
        ->assertExitCode(0);

    $package = Package::query()->where('name', $name)->sole();

    expect($package->vendor_id)->toBeNull()
        ->and($package->ecosystem)->toBe(Ecosystem::Npm);

    Queue::assertPushed(FetchPackageDetailsJob::class);
})->with([
    'scoped' => ['@scope/name'],
    'unscoped' => ['lodash'],
]);

it('rejects a scoped-looking npm name with no package part', function () {
    $this->artisan('packages:scan-package', ['package' => '@to-safe-integer', 'ecosystem' => 'npm'])
        ->assertExitCode(1);

    expect(Package::query()->count())->toBe(0);
});

it('rejects an unscoped npm name containing a slash', function () {
    $this->artisan('packages:scan-package', ['package' => 'foo/bar/baz', 'ecosystem' => 'npm'])
        ->assertExitCode(1);

    expect(Package::query()->count())->toBe(0);
});

it('is idempotent when scanning the same package twice', function () {
    Queue::fake([FetchPackageDetailsJob::class]);

    $this->artisan('packages:scan-package', ['package' => 'acme/widget'])->assertExitCode(0);
    $this->artisan('packages:scan-package', ['package' => 'acme/widget'])->assertExitCode(0);

    expect(Package::query()->where('name', 'acme/widget')->count())->toBe(1);
});
