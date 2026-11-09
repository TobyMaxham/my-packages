<?php

namespace App\Services;

use App\Enums\PackageStatus;
use Carbon\CarbonInterface;
use Composer\Semver\Semver;

class CompatibilityChecker
{
    /**
     * A package without a fresh release is only given the benefit of the
     * doubt ("unclear") for this many months before it is flagged "critical".
     */
    private const STALE_MONTHS = 12;

    /**
     * Determine the three-tier compatibility status of a package against
     * the required and recommended versions in config/package_targets.php.
     *
     * - critical: the package is abandoned, or it misses a required version
     *   and looks unmaintained (no release in the last STALE_MONTHS).
     * - unclear: it misses a required version but was released recently,
     *   or it covers all required versions but misses a recommended one.
     * - compatible: it covers every required and recommended version.
     *
     * @param  array<int, string>  $frameworkConstraints  every Laravel constraint of the
     *                                                    latest release (laravel/framework,
     *                                                    illuminate/*); all of them must
     *                                                    allow a target version.
     */
    public function determine(
        ?string $phpConstraint,
        array $frameworkConstraints,
        bool $abandoned,
        ?CarbonInterface $lastReleaseAt,
    ): PackageStatus {
        if ($abandoned) {
            return PackageStatus::Critical;
        }

        /** @var array{php: array{required: array<int, string>, recommended: array<int, string>}, laravel: array{required: array<int, string>, recommended: array<int, string>}} $targets */
        $targets = config('package_targets');

        $coversRequired = $this->allowsAll($targets['php']['required'], [$phpConstraint])
            && $this->allowsAll($targets['laravel']['required'], $frameworkConstraints);

        if (! $coversRequired) {
            return $this->isRecentlyReleased($lastReleaseAt)
                ? PackageStatus::Unclear
                : PackageStatus::Critical;
        }

        $coversRecommended = $this->allowsAll($targets['php']['recommended'], [$phpConstraint])
            && $this->allowsAll($targets['laravel']['recommended'], $frameworkConstraints);

        return $coversRecommended ? PackageStatus::Compatible : PackageStatus::Unclear;
    }

    /**
     * @param  array<int, string>  $targetVersions
     * @param  array<int, string|null>  $constraints
     */
    private function allowsAll(array $targetVersions, array $constraints): bool
    {
        foreach ($targetVersions as $targetVersion) {
            foreach ($constraints as $constraint) {
                if (! $this->satisfies($targetVersion, $constraint)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function satisfies(string $targetVersion, ?string $constraint): bool
    {
        if ($constraint === null || trim($constraint) === '' || trim($constraint) === '*') {
            return true;
        }

        try {
            return Semver::satisfies($targetVersion, $constraint);
        } catch (\UnexpectedValueException) {
            return false;
        }
    }

    private function isRecentlyReleased(?CarbonInterface $lastReleaseAt): bool
    {
        return $lastReleaseAt !== null
            && $lastReleaseAt->greaterThan(now()->subMonths(self::STALE_MONTHS));
    }
}
