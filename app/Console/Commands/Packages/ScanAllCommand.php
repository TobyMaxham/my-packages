<?php

namespace App\Console\Commands\Packages;

use App\Models\Vendor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('packages:scan-all')]
#[Description('Re-scan every known vendor via packages:scan-vendor')]
class ScanAllCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $vendors = Vendor::query()->get();

        if ($vendors->isEmpty()) {
            $this->components->warn('No vendors are tracked yet. Add one with packages:scan-vendor.');

            return self::SUCCESS;
        }

        foreach ($vendors as $vendor) {
            $this->call('packages:scan-vendor', [
                'vendor' => $vendor->name,
                'ecosystem' => $vendor->ecosystem->value,
            ]);
        }

        return self::SUCCESS;
    }
}
