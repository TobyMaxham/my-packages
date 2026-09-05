<?php

namespace App\Console\Commands\Packages;

use App\Enums\Ecosystem;
use App\Jobs\FetchPackageDetailsJob;
use App\Models\Package;
use App\Models\Vendor;
use App\Services\PackagistClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('packages:scan-vendor {vendor : The vendor/namespace to scan} {ecosystem=composer : The package ecosystem (composer or npm)}')]
#[Description('Fetch a vendor\'s package list and queue a compatibility check for each package')]
class ScanVendorCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PackagistClient $packagist): int
    {
        $vendorName = (string) $this->argument('vendor');
        $ecosystem = Ecosystem::tryFrom((string) $this->argument('ecosystem'));

        if ($ecosystem === null) {
            $this->components->error('Unknown ecosystem. Use "composer" or "npm".');

            return self::FAILURE;
        }

        if ($ecosystem === Ecosystem::Npm) {
            $this->components->warn('npm scanning is planned but not implemented yet.');

            return self::SUCCESS;
        }

        $packageNames = $packagist->packageNamesForVendor($vendorName);

        if ($packageNames === []) {
            $this->components->warn("No packages found for vendor \"{$vendorName}\" on Packagist.");

            return self::SUCCESS;
        }

        $vendor = Vendor::query()->firstOrCreate([
            'name' => $vendorName,
            'ecosystem' => $ecosystem,
        ]);

        foreach ($packageNames as $packageName) {
            $package = Package::query()->firstOrCreate(
                ['ecosystem' => $ecosystem, 'name' => $packageName],
                ['vendor_id' => $vendor->id],
            );

            FetchPackageDetailsJob::dispatch($package);
        }

        $this->components->info(sprintf(
            'Queued %d package(s) for vendor "%s".',
            count($packageNames),
            $vendorName,
        ));

        return self::SUCCESS;
    }
}
