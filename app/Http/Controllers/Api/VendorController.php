<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;

class VendorController extends Controller
{
    /**
     * List the distinct, tracked vendor names for use in filter dropdowns.
     */
    public function index(): JsonResponse
    {
        $names = Vendor::query()->orderBy('name')->pluck('name')->unique()->values();

        return response()->json(['data' => $names]);
    }
}
