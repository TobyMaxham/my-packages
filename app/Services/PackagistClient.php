<?php

namespace App\Services;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PackagistClient
{
    private const BASE_URL = 'https://packagist.org';

    /**
     * List all package names for a given Composer vendor.
     *
     * Any failure is treated as "no packages" rather than thrown — a
     * Packagist hiccup must never crash packages:scan-all mid-run.
     *
     * @return array<int, string>
     */
    public function packageNamesForVendor(string $vendor): array
    {
        $response = Http::baseUrl(self::BASE_URL)->get('/packages/list.json', ['vendor' => $vendor]);

        if ($response->failed()) {
            Log::warning("Packagist vendor lookup for \"{$vendor}\" failed", ['status' => $response->status()]);

            return [];
        }

        return $response->json('packageNames') ?? [];
    }

    /**
     * Fetch the raw package details payload for a single package.
     *
     * Any failure (missing package, malformed name, Packagist outage, ...)
     * is treated as "no data available" rather than thrown — a single bad
     * package must never crash a scan.
     *
     * @return array<string, mixed>|null
     */
    public function packageDetails(string $vendor, string $package): ?array
    {
        $response = Http::baseUrl(self::BASE_URL)->get("/packages/{$vendor}/{$package}.json");

        if ($response->failed()) {
            if (! $response->notFound()) {
                Log::warning("Packagist package lookup for \"{$vendor}/{$package}\" failed", ['status' => $response->status()]);
            }

            return null;
        }

        return $response->json('package');
    }

    /**
     * Pick the latest stable version entry from a package's "versions" map,
     * ignoring dev branches (e.g. "dev-main"). Falls back to the latest
     * non-stable version if no stable release exists.
     *
     * @param  array<string, array<string, mixed>>  $versions
     * @return array<string, mixed>|null
     */
    public function latestVersion(array $versions): ?array
    {
        $candidates = array_filter(
            $versions,
            fn (string $version): bool => VersionParser::parseStability($version) !== 'dev',
            ARRAY_FILTER_USE_KEY,
        );

        $stable = array_filter(
            $candidates,
            fn (string $version): bool => VersionParser::parseStability($version) === 'stable',
            ARRAY_FILTER_USE_KEY,
        );

        $pool = $stable !== [] ? $stable : $candidates;

        if ($pool === []) {
            return null;
        }

        uksort($pool, fn (string $a, string $b): int => Comparator::lessThan($a, $b) ? 1 : -1);

        return reset($pool);
    }
}
