<?php

namespace App\Services;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PackagistClient
{
    private const BASE_URL = 'https://packagist.org';

    private const REPO_URL = 'https://repo.packagist.org';

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
     * Fetch all tagged versions from the Composer v2 metadata endpoint,
     * keyed by version name.
     *
     * Unlike /packages/{name}.json (CDN-cached for up to 12 hours), this
     * endpoint is only cached for minutes, so fresh releases show up on
     * the next scan. Failures return null so callers can fall back.
     *
     * @return array<string, array<string, mixed>>|null
     */
    public function releasedVersions(string $vendor, string $package): ?array
    {
        return $this->metadataVersions($vendor, $package, "/p2/{$vendor}/{$package}.json");
    }

    /**
     * Fetch the default branch (e.g. dev-main) from the Composer v2 dev
     * metadata. Used for packages that never tagged a release, so their
     * composer.json constraints can still be checked.
     *
     * @return array<string, mixed>|null
     */
    public function defaultBranchVersion(string $vendor, string $package): ?array
    {
        $versions = $this->metadataVersions($vendor, $package, "/p2/{$vendor}/{$package}~dev.json") ?? [];

        foreach ($versions as $version) {
            if (($version['default-branch'] ?? false) === true) {
                return $version;
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    private function metadataVersions(string $vendor, string $package, string $path): ?array
    {
        $response = Http::baseUrl(self::REPO_URL)->get($path);

        if ($response->failed()) {
            if (! $response->notFound()) {
                Log::warning("Packagist metadata lookup for \"{$vendor}/{$package}\" failed", ['status' => $response->status()]);
            }

            return null;
        }

        $entries = $response->json('packages')["{$vendor}/{$package}"] ?? null;

        if (! is_array($entries) || $entries === []) {
            return null;
        }

        $isMinified = $response->json('minified') === 'composer/2.0';
        $versions = [];
        $previous = [];

        foreach ($entries as $entry) {
            $expanded = $isMinified ? $this->expandMinifiedEntry($previous, $entry) : $entry;

            if (isset($expanded['version'])) {
                $versions[$expanded['version']] = $expanded;
            }

            $previous = $expanded;
        }

        return $versions;
    }

    /**
     * Composer v2 metadata only lists the fields that changed compared to
     * the previous version; "__unset" marks a field that was removed.
     *
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function expandMinifiedEntry(array $previous, array $entry): array
    {
        foreach ($entry as $key => $value) {
            if ($value === '__unset') {
                unset($previous[$key]);
            } else {
                $previous[$key] = $value;
            }
        }

        return $previous;
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

        $parser = new VersionParser;

        uksort($pool, fn (string $a, string $b): int => Comparator::lessThan($parser->normalize($a), $parser->normalize($b)) ? 1 : -1);

        return reset($pool);
    }
}
