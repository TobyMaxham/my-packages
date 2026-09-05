<?php

namespace App\Http\Resources;

use App\Models\Package;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Full, unfiltered package export for authenticated bulk analysis (e.g. by
 * an LLM) — every field, including the admin-only compatibility verdict.
 * Deliberately separate from PackageResource, which stays the public,
 * neutral-fields-only contract and must not be touched by this class.
 *
 * @mixin Package
 */
class PackageExportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastReleaseAt = $this->last_release_at;
        $daysSinceLastRelease = $lastReleaseAt !== null ? (int) $lastReleaseAt->diffInDays(now()) : null;

        return [
            'name' => $this->name,
            'vendor' => $this->vendor?->name ?? (str_contains($this->name, '/') ? Str::before($this->name, '/') : null),
            'ecosystem' => $this->ecosystem,
            'repository_url' => $this->repository_url,
            'latest_version' => $this->latest_version,
            'php_constraint' => $this->php_constraint,
            'framework_constraint' => $this->framework_constraint,
            'last_release_at' => self::formatUtc($lastReleaseAt),
            // Computed server-side on purpose — the consumer (an LLM) must
            // never have to do this arithmetic itself.
            'days_since_last_release' => $daysSinceLastRelease,
            'years_since_last_release' => $daysSinceLastRelease !== null
                ? round($daysSinceLastRelease / 365, 1)
                : null,
            'abandoned' => $this->abandoned,
            'status' => $this->status,
            'downloads_total' => $this->downloads_total,
            'favers' => $this->favers,
            'last_checked_at' => self::formatUtc($this->last_checked_at),
        ];
    }

    /**
     * Render a date as an unambiguous UTC ISO-8601 string, e.g.
     * "2026-09-05T10:00:00Z" — never a Unix timestamp or a localized string.
     */
    public static function formatUtc(?CarbonInterface $date): ?string
    {
        return $date?->clone()->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
