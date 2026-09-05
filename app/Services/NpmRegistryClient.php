<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NpmRegistryClient
{
    private const BASE_URL = 'https://registry.npmjs.org';

    /**
     * Fetch the raw packument for a single npm package. Works for both
     * scoped ("@scope/name") and unscoped ("name") packages.
     *
     * Any failure (missing package, malformed name, registry outage, ...)
     * is treated as "no data available" rather than thrown — a single bad
     * package must never crash a scan.
     *
     * @return array<string, mixed>|null
     */
    public function packageDetails(string $name): ?array
    {
        $response = Http::baseUrl(self::BASE_URL)->get('/'.rawurlencode($name));

        if ($response->failed()) {
            if (! $response->notFound()) {
                Log::warning("npm registry lookup for \"{$name}\" failed", ['status' => $response->status()]);
            }

            return null;
        }

        return $response->json();
    }

    /**
     * Resolve the "latest" dist-tag version entry from a packument.
     *
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>|null
     */
    public function latestVersion(array $details): ?array
    {
        $latestTag = $details['dist-tags']['latest'] ?? null;

        if ($latestTag === null) {
            return null;
        }

        return $details['versions'][$latestTag] ?? null;
    }

    /**
     * npm repository URLs commonly look like "git+https://host/x.git" or
     * "git://host/x.git" — normalize to a plain https URL.
     */
    public function normalizeRepositoryUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = preg_replace('#^git\+#', '', $url);
        $url = preg_replace('#^git://#', 'https://', $url);
        $url = preg_replace('#\.git$#', '', $url);

        return $url;
    }
}
