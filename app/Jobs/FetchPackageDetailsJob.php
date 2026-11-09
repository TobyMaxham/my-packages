<?php

namespace App\Jobs;

use App\Enums\Ecosystem;
use App\Models\Package;
use App\Services\CompatibilityChecker;
use App\Services\NpmRegistryClient;
use App\Services\PackagistClient;
use Carbon\Carbon;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Intervals;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
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
        $release = $packagist->latestVersion($versions);
        $hasRelease = $release !== null;

        // Without any tagged release, fall back to the default branch so its
        // composer.json constraints are still checked. Its commit time is not
        // a release date, so last_release_at/latest_version stay empty.
        $defaultBranch = $packagist->defaultBranchVersion($vendorSlug, $packageSlug);
        $latest = $release ?? $defaultBranch ?? [];
        $require = $latest['require'] ?? [];

        $phpConstraint = $require['php'] ?? null;
        $frameworkConstraints = $this->frameworkConstraints($require);
        $lastReleaseAt = $hasRelease && isset($release['time']) ? Carbon::parse($release['time']) : null;
        $abandoned = ! in_array($details['abandoned'] ?? false, [false, null], true);

        $status = $checker->determine(
            $phpConstraint,
            $frameworkConstraints,
            $abandoned,
            $lastReleaseAt,
            hasRelease: $hasRelease,
        );

        $this->package->update([
            'description' => $latest['description'] ?? $details['description'] ?? $this->package->description,
            'tags' => array_values($latest['keywords'] ?? []),
            'repository_url' => $details['repository'] ?? $this->package->repository_url,
            'abandoned' => $abandoned,
            'latest_version' => $hasRelease ? $release['version'] ?? null : null,
            'php_constraint' => $phpConstraint,
            'framework_constraint' => $frameworkConstraints === [] ? null : implode(' & ', $frameworkConstraints),
            'outdated_dependencies' => $this->outdatedDependencies($require),
            'unreleased_changes' => $hasRelease && $defaultBranch !== null
                ? $this->unreleasedConstraintChanges($require, $defaultBranch['require'] ?? [])
                : [],
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
     * Find dependencies on other tracked composer packages whose constraint
     * does not allow that package's latest known version — a hint that this
     * package pins an outdated release of one of our own packages.
     *
     * The comparison uses the dependency's latest_version as stored, so a
     * release found in the same scan may only show up on the next one.
     *
     * @param  array<string, string>  $require
     * @return array<int, array{name: string, constraint: string, latest_version: string}>
     */
    private function outdatedDependencies(array $require): array
    {
        $trackedDependencies = Package::query()
            ->where('ecosystem', Ecosystem::Composer)
            ->whereIn('name', array_keys($require))
            ->whereKeyNot($this->package->getKey())
            ->whereNotNull('latest_version')
            ->orderBy('name')
            ->get(['name', 'latest_version']);

        $outdated = [];

        foreach ($trackedDependencies as $dependency) {
            $constraint = $require[$dependency->name];

            try {
                if (Semver::satisfies($dependency->latest_version, $constraint)) {
                    continue;
                }
            } catch (\UnexpectedValueException) {
                continue;
            }

            $outdated[] = [
                'name' => $dependency->name,
                'constraint' => $constraint,
                'latest_version' => $dependency->latest_version,
            ];
        }

        return $outdated;
    }

    /**
     * Compare the PHP and Laravel constraints of the latest release with
     * the default branch — a difference means the branch already supports
     * (or drops) versions that no tagged release ships yet.
     *
     * @param  array<string, string>  $releaseRequire
     * @param  array<string, string>  $branchRequire
     * @return array<int, array{type: string, release: string|null, default_branch: string|null}>
     */
    private function unreleasedConstraintChanges(array $releaseRequire, array $branchRequire): array
    {
        $changes = [];

        $releasePhp = $releaseRequire['php'] ?? null;
        $branchPhp = $branchRequire['php'] ?? null;

        if (! $this->sameConstraints(array_filter([$releasePhp]), array_filter([$branchPhp]))) {
            $changes[] = ['type' => 'php', 'release' => $releasePhp, 'default_branch' => $branchPhp];
        }

        $releaseFramework = $this->frameworkConstraints($releaseRequire);
        $branchFramework = $this->frameworkConstraints($branchRequire);

        if (! $this->sameConstraints($releaseFramework, $branchFramework)) {
            $changes[] = [
                'type' => 'laravel',
                'release' => $releaseFramework === [] ? null : implode(' & ', $releaseFramework),
                'default_branch' => $branchFramework === [] ? null : implode(' & ', $branchFramework),
            ];
        }

        return $changes;
    }

    /**
     * Whether two sets of constraints (each set combined with AND) allow
     * exactly the same versions, so "^12|^13" equals "^12.0 || ^13.0".
     *
     * @param  array<int, string>  $first
     * @param  array<int, string>  $second
     */
    private function sameConstraints(array $first, array $second): bool
    {
        try {
            return $this->normalizeConstraints($first) === $this->normalizeConstraints($second);
        } catch (\UnexpectedValueException) {
            return $first === $second;
        }
    }

    /**
     * @param  array<int, string>  $constraints
     */
    private function normalizeConstraints(array $constraints): string
    {
        $parser = new VersionParser;
        $parsed = array_map(fn (string $constraint) => $parser->parseConstraints($constraint), $constraints);

        return (string) Intervals::compactConstraint(MultiConstraint::create($parsed, true));
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

        $status = $checker->determine(null, [], $abandoned, $lastReleaseAt, ecosystem: Ecosystem::Npm);

        $this->package->update([
            'description' => $details['description'] ?? $this->package->description,
            'tags' => array_values($latest['keywords'] ?? $details['keywords'] ?? []),
            'repository_url' => $repositoryUrl ?? $this->package->repository_url,
            'abandoned' => $abandoned,
            'latest_version' => $latestTag ?? $this->package->latest_version,
            'php_constraint' => null,
            'framework_constraint' => null,
            'outdated_dependencies' => null,
            'unreleased_changes' => null,
            'last_release_at' => $lastReleaseAt,
            'status' => $status,
            'downloads_total' => 0,
            'favers' => 0,
            'last_checked_at' => now(),
        ]);
    }
}
