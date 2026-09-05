<?php

namespace App\Jobs;

use App\Enums\Ecosystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class ScanVendorJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $vendor,
        public readonly Ecosystem $ecosystem,
    ) {
        //
    }

    public function handle(): void
    {
        Artisan::call('packages:scan-vendor', [
            'vendor' => $this->vendor,
            'ecosystem' => $this->ecosystem->value,
        ]);
    }
}
