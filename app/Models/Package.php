<?php

namespace App\Models;

use App\Enums\Ecosystem;
use App\Enums\PackageStatus;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vendor_id',
    'ecosystem',
    'name',
    'description',
    'tags',
    'repository_url',
    'abandoned',
    'latest_version',
    'php_constraint',
    'framework_constraint',
    'outdated_dependencies',
    'unreleased_changes',
    'last_release_at',
    'status',
    'is_featured',
    'featured_note',
    'downloads_total',
    'favers',
    'last_checked_at',
])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ecosystem' => Ecosystem::class,
            'status' => PackageStatus::class,
            'tags' => 'array',
            'outdated_dependencies' => 'array',
            'unreleased_changes' => 'array',
            'abandoned' => 'boolean',
            'is_featured' => 'boolean',
            'last_release_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'downloads_total' => 'integer',
            'favers' => 'integer',
        ];
    }
}
