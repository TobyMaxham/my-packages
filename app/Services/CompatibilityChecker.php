<?php

namespace App\Services;

use App\Enums\Ecosystem;
use App\Enums\PackageStatus;
use Carbon\CarbonInterface;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

class CompatibilityChecker
{
    /**
     * A package without a fresh release is only given the benefit of the
     * doubt ("unclear") for this many months before it is flagged "critical".
     */
    private const STALE_MONTHS = 12;

    /**
     * A composer package without a bounded Laravel constraint (none at all,
     * or only open ones like ">=5.5") whose last release is older than this
     * was most likely never checked against current Laravel versions, so it
     * is no longer read as "compatible".
     */
    private const UNCONSTRAINED_MAX_AGE_YEARS = 3;

    /**
     * Determine the three-tier compatibility status of a package against
     * the required and recommended versions in config/package_targets.php.
     *
     * - abandoned: the package is marked abandoned/deprecated. It is no
     *   longer maintained, so it is never flagged "critical".
     * - critical: it misses a required version and looks unmaintained
     *   (no release in the last STALE_MONTHS).
     * - unclear: it misses a required version but was released recently;
     *   or it covers all required versions but has no tagged release, has
     *   no bounded Laravel constraint and a release older than
     *   UNCONSTRAINED_MAX_AGE_YEARS (composer only), or misses a
     *   recommended version.
     * - compatible: it covers every required and recommended version.
     *
     * @param  array<int, string>  $frameworkConstraints  every Laravel constraint of the
     *                                                    latest release (laravel/framework,
     *                                                    illuminate/*); all of them must
     *                                                    allow a target version.
     * @param  bool  $hasRelease  false when only the default branch could be checked
     */
    public function determine(
        ?string $phpConstraint,
        array $frameworkConstraints,
        bool $abandoned,
        ?CarbonInterface $lastReleaseAt,
        bool $hasRelease = true,
        Ecosystem $ecosystem = Ecosystem::Composer,
    ): PackageStatus {
        if ($abandoned) {
            return PackageStatus::Abandoned;
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

        if (! $hasRelease) {
            return PackageStatus::Unclear;
        }

        if ($ecosystem === Ecosystem::Composer
            && ! $this->hasUpperBound($frameworkConstraints)
            && $lastReleaseAt !== null
            && $lastReleaseAt->lessThan(now()->subYears(self::UNCONSTRAINED_MAX_AGE_YEARS))) {
            return PackageStatus::Unclear;
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

    /**
     * Whether at least one constraint caps the allowed versions, e.g.
     * "^12.0" or "9 - 11" — unlike ">=5.5" or "*", which allow any future
     * major and therefore say nothing about real compatibility.
     *
     * @param  array<int, string>  $constraints
     */
    private function hasUpperBound(array $constraints): bool
    {
        $parser = new VersionParser;

        foreach ($constraints as $constraint) {
            try {
                if (! $parser->parseConstraints($constraint)->getUpperBound()->isPositiveInfinity()) {
                    return true;
                }
            } catch (\UnexpectedValueException) {
                return true;
            }
        }

        return false;
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
