<?php

use App\Enums\Ecosystem;
use App\Jobs\FetchPackageDetailsJob;
use App\Models\Package;
use Illuminate\Support\Facades\Queue;

it('re-checks standalone packages that belong to no vendor', function () {
    Queue::fake([FetchPackageDetailsJob::class]);

    $package = Package::factory()->create([
        'vendor_id' => null,
        'ecosystem' => Ecosystem::Npm,
        'name' => '@acme/cli',
    ]);

    $this->artisan('packages:scan-all')->assertExitCode(0);

    Queue::assertPushed(FetchPackageDetailsJob::class, fn (FetchPackageDetailsJob $job): bool => $job->package->is($package));
});
