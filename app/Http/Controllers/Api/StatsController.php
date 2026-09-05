<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /**
     * Public, purely informational aggregate counters (no per-package
     * judgment/evaluation) for the package list header.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'packages_count' => Package::query()->count(),
            'vendors_count' => Package::query()->whereNotNull('vendor_id')->distinct()->count('vendor_id'),
            'downloads_total' => (int) Package::query()->sum('downloads_total'),
            'favers_total' => (int) Package::query()->sum('favers'),
        ]);
    }
}
