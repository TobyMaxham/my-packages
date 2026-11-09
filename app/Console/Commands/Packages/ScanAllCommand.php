<?php

namespace App\Console\Commands\Packages;

use App\Jobs\FetchPackageDetailsJob;
use App\Models\Package;
use App\Models\Vendor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('packages:scan-all')]
#[Description('Re-scan every known vendor and every standalone package')]
class ScanAllCommand extends Command
{
    /**
     * Execute the console command.
     *
     * Standalone packages (added via packages:scan-package, e.g. npm
     * packages) belong to no vendor, so they are re-checked separately.
     */
    public function handle(): int
    {
        $vendors = Vendor::query()->get();
        $standalonePackages = Package::query()->whereNull('vendor_id')->get();

        if ($vendors->isEmpty() && $standalonePackages->isEmpty()) {
            $this->components->warn('Nothing is tracked yet. Add a vendor with packages:scan-vendor or a package with packages:scan-package.');

            return self::SUCCESS;
        }

        foreach ($vendors as $vendor) {
            $this->call('packages:scan-vendor', [
                'vendor' => $vendor->name,
                'ecosystem' => $vendor->ecosystem->value,
            ]);
        }

        foreach ($standalonePackages as $package) {
            FetchPackageDetailsJob::dispatch($package);
        }

        if ($standalonePackages->isNotEmpty()) {
            $this->components->info("Queued {$standalonePackages->count()} standalone package(s).");
        }

        return self::SUCCESS;
    }
}
