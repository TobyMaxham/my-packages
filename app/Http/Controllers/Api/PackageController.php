<?php

namespace App\Http\Controllers\Api;

use App\Enums\Ecosystem;
use App\Enums\PackageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PackageController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Package::query()->with('vendor');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', "%{$search}%"));
            });
        }

        if ($ecosystem = Ecosystem::tryFrom((string) $request->query('ecosystem'))) {
            $query->where('ecosystem', $ecosystem);
        }

        if ($vendorId = $request->integer('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        } elseif ($vendorName = $request->string('vendor')->trim()->value()) {
            $query->whereHas('vendor', fn ($vendor) => $vendor->where('name', $vendorName));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // status and stale_months are evaluative/action-oriented filters, so
        // they stay admin-only — same boundary as the resource's gated fields.
        if ($request->user('sanctum') !== null) {
            if ($status = PackageStatus::tryFrom((string) $request->query('status'))) {
                $query->where('status', $status);
            }

            if ($staleMonths = $request->integer('stale_months')) {
                $threshold = now()->subMonths($staleMonths);
                $query->where(function ($query) use ($threshold) {
                    $query->whereNull('last_release_at')->orWhere('last_release_at', '<=', $threshold);
                });
            }
        }

        return PackageResource::collection(
            $query->orderByRaw('last_release_at IS NULL')
                ->orderByDesc('last_release_at')
                ->orderBy('name')
                ->paginate(self::PER_PAGE)
                ->withQueryString()
        );
    }

    public function show(Package $package): PackageResource
    {
        return PackageResource::make($package->load('vendor'));
    }
}
