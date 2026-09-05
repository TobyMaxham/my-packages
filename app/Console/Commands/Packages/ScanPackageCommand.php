<?php

namespace App\Console\Commands\Packages;

use App\Enums\Ecosystem;
use App\Jobs\FetchPackageDetailsJob;
use App\Models\Package;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('packages:scan-package {package : Full package name, e.g. "vendor/package" (composer), "@scope/package" or "package" (npm)} {ecosystem=composer : The package ecosystem (composer or npm)}')]
#[Description('Track a single package that is not part of a tracked vendor (e.g. an unscoped npm package)')]
class ScanPackageCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $packageName = trim((string) $this->argument('package'));
        $ecosystem = Ecosystem::tryFrom((string) $this->argument('ecosystem'));

        if ($ecosystem === null) {
            $this->components->error('Unknown ecosystem. Use "composer" or "npm".');

            return self::FAILURE;
        }

        if ($ecosystem === Ecosystem::Composer && substr_count($packageName, '/') !== 1) {
            $this->components->error('Composer packages must be given as "vendor/package".');

            return self::FAILURE;
        }

        if ($packageName === '') {
            $this->components->error('Package name must not be empty.');

            return self::FAILURE;
        }

        if ($ecosystem === Ecosystem::Npm) {
            $isScoped = str_starts_with($packageName, '@');
            $slashCount = substr_count($packageName, '/');

            if ($isScoped && $slashCount !== 1) {
                $this->components->error('Scoped npm packages must be given as "@scope/package".');

                return self::FAILURE;
            }

            if (! $isScoped && $slashCount !== 0) {
                $this->components->error('Unscoped npm packages must not contain a "/".');

                return self::FAILURE;
            }
        }

        // Deliberately no vendor_id here — this command is for packages
        // that aren't part of a tracked vendor (e.g. an unscoped npm
        // package like "lodash", or a one-off composer package).
        $package = Package::query()->firstOrCreate(
            ['ecosystem' => $ecosystem, 'name' => $packageName],
            ['vendor_id' => null],
        );

        FetchPackageDetailsJob::dispatch($package);

        $this->components->info("Queued \"{$packageName}\" for a compatibility check.");

        return self::SUCCESS;
    }
}
