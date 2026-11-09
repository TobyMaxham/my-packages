<?php

namespace App\Jobs;

use App\Enums\Ecosystem;
use App\Models\Package;
use App\Services\CompatibilityChecker;
use App\Services\NpmRegistryClient;
use App\Services\PackagistClient;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchPackageDetailsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Package $package)
    {
        //
    }

    public function handle(PackagistClient $packagist, NpmRegistryClient $npm, CompatibilityChecker $checker): void
    {
        match ($this->package->ecosystem) {
            Ecosystem::Composer => $this->syncFromComposer($packagist, $checker),
            Ecosystem::Npm => $this->syncFromNpm($npm, $checker),
        };
    }

    private function syncFromComposer(PackagistClient $packagist, CompatibilityChecker $checker): void
    {
        [$vendorSlug, $packageSlug] = array_pad(explode('/', $this->package->name, 2), 2, null);

        if ($vendorSlug === null || $packageSlug === null) {
            return;
        }

        $details = $packagist->packageDetails($vendorSlug, $packageSlug);

        if ($details === null) {
            return;
        }

        $versions = $packagist->releasedVersions($vendorSlug, $packageSlug) ?? $details['versions'] ?? [];
        $latest = $packagist->latestVersion($versions);
        $require = $latest['require'] ?? [];

        $phpConstraint = $require['php'] ?? null;
        $frameworkConstraints = $this->frameworkConstraints($require);
        $lastReleaseAt = isset($latest['time']) ? Carbon::parse($latest['time']) : null;
        $abandoned = ! in_array($details['abandoned'] ?? false, [false, null], true);

        $status = $checker->determine($phpConstraint, $frameworkConstraints, $abandoned, $lastReleaseAt);

        $this->package->update([
            'description' => $latest['description'] ?? $details['description'] ?? $this->package->description,
            'tags' => array_values($latest['keywords'] ?? []),
            'repository_url' => $details['repository'] ?? $this->package->repository_url,
            'abandoned' => $abandoned,
            'latest_version' => $latest['version'] ?? $this->package->latest_version,
            'php_constraint' => $phpConstraint,
            'framework_constraint' => $frameworkConstraints === [] ? null : implode(' & ', $frameworkConstraints),
            'last_release_at' => $lastReleaseAt,
            'status' => $status,
            'downloads_total' => $details['downloads']['total'] ?? 0,
            'favers' => $details['favers'] ?? 0,
            'last_checked_at' => now(),
        ]);
    }

    /**
     * Collect the distinct Laravel constraints a release depends on, from
     * laravel/framework and every illuminate/* component.
     *
     * @param  array<string, string>  $require
     * @return array<int, string>
     */
    private function frameworkConstraints(array $require): array
    {
        $constraints = array_filter(
            $require,
            fn (string $package): bool => $package === 'laravel/framework' || str_starts_with($package, 'illuminate/'),
            ARRAY_FILTER_USE_KEY,
        );

        return array_values(array_unique($constraints));
    }

    /**
     * npm has no equivalent of a PHP/Laravel constraint (it declares a Node
     * "engines" range instead), so php_constraint/framework_constraint stay
     * null here and the compatibility verdict defaults to "compatible"
     * unless the package is abandoned or stale — it does not mean the
     * package was actually checked against a PHP/Laravel target.
     */
    private function syncFromNpm(NpmRegistryClient $npm, CompatibilityChecker $checker): void
    {
        $details = $npm->packageDetails($this->package->name);

        if ($details === null) {
            return;
        }

        $latest = $npm->latestVersion($details) ?? [];
        $latestTag = $details['dist-tags']['latest'] ?? null;

        $lastReleaseAt = $latestTag !== null && isset($details['time'][$latestTag])
            ? Carbon::parse($details['time'][$latestTag])
            : null;
        $abandoned = ! empty($latest['deprecated']);
        $repositoryUrl = $npm->normalizeRepositoryUrl(
            $latest['repository']['url'] ?? $details['repository']['url'] ?? null
        );

        $status = $checker->determine(null, [], $abandoned, $lastReleaseAt);

        $this->package->update([
            'description' => $details['description'] ?? $this->package->description,
            'tags' => array_values($latest['keywords'] ?? $details['keywords'] ?? []),
            'repository_url' => $repositoryUrl ?? $this->package->repository_url,
            'abandoned' => $abandoned,
            'latest_version' => $latestTag ?? $this->package->latest_version,
            'php_constraint' => null,
            'framework_constraint' => null,
            'last_release_at' => $lastReleaseAt,
            'status' => $status,
            'downloads_total' => 0,
            'favers' => 0,
            'last_checked_at' => now(),
        ]);
    }
}
