<?php

namespace App\Http\Resources;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Package
 */
class PackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAuthenticated = $request->user('sanctum') !== null;

        return [
            // Neutral, non-evaluative facts — visible to everyone.
            'id' => $this->id,
            'name' => $this->name,
            // Unscoped npm packages (e.g. "lodash") have no vendor at all —
            // don't fall back to the package name itself in that case.
            'vendor' => $this->vendor?->name ?? (str_contains($this->name, '/') ? Str::before($this->name, '/') : null),
            'ecosystem' => $this->ecosystem,
            'tags' => $this->tags ?? [],
            'description' => $this->description,
            'github_url' => $this->repository_url,
            'latest_version' => $this->latest_version,
            'last_release_at' => $this->last_release_at,
            'php_range' => $this->php_constraint,
            'laravel_range' => $this->framework_constraint,
            'is_featured' => $this->is_featured,
            'featured_note' => $this->featured_note,

            // The computed verdict and anything action-oriented is admin-only
            // by design — these keys must be absent, not just hidden, from
            // an anonymous response.
            'status' => $this->when($isAuthenticated, $this->status),
            'abandoned' => $this->when($isAuthenticated, $this->abandoned),
            'downloads_total' => $this->when($isAuthenticated, $this->downloads_total),
            'favers' => $this->when($isAuthenticated, $this->favers),
            'last_checked_at' => $this->when($isAuthenticated, $this->last_checked_at),
            'outdated_dependencies' => $this->when($isAuthenticated, $this->outdated_dependencies ?? []),
        ];
    }
}
