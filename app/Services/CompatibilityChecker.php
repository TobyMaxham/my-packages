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
     * Determine the three-tier compatibility status of a package.
     *
     * - compatible: the constraint of the latest version covers the target.
     * - unclear: it doesn't cover the target, but the package still looks
     *   active (released recently and not abandoned) — it may simply not
     *   have picked up the new target version yet.
     * - critical: it doesn't cover the target and the package looks
     *   unmaintained (stale release or abandoned).
     */
    public function determine(
        ?string $phpConstraint,
        ?string $frameworkConstraint,
        bool $abandoned,
        ?CarbonInterface $lastReleaseAt,
    ): PackageStatus {
        if ($this->coversTargets($phpConstraint, $frameworkConstraint)) {
            return PackageStatus::Compatible;
        }

        if (! $abandoned && $this->isRecentlyReleased($lastReleaseAt)) {
            return PackageStatus::Unclear;
        }

        return PackageStatus::Critical;
    }

    private function coversTargets(?string $phpConstraint, ?string $frameworkConstraint): bool
    {
        /** @var array{php: string, laravel: array<int, string>} $targets */
        $targets = config('package_targets');

        if (! $this->satisfies($targets['php'], $phpConstraint)) {
            return false;
        }

        if ($frameworkConstraint === null) {
            return true;
        }

        foreach ($targets['laravel'] as $laravelVersion) {
            if ($this->satisfies($laravelVersion, $frameworkConstraint)) {
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
