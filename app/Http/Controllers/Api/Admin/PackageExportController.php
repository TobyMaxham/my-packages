<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PackageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PackageExportResource;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageExportController extends Controller
{
    /**
     * Full, unpaginated package export for a one-off bulk analysis (e.g. by
     * an LLM) — always the complete list, never paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $packages = Package::query()->with('vendor')->orderBy('name')->get();

        return response()->json([
            'generated_at' => PackageExportResource::formatUtc(now()),
            'target_versions' => config('package_targets'),
            'status_legend' => PackageStatus::legend(),
            'packages' => $packages
                ->map(fn (Package $package) => (new PackageExportResource($package))->toArray($request))
                ->all(),
        ]);
    }
}
