<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Ecosystem;
use App\Http\Controllers\Controller;
use App\Jobs\ScanVendorJob;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(): View
    {
        $vendors = Vendor::query()
            ->withCount('packages')
            ->orderBy('name')
            ->get();

        return view('admin.vendors.index', ['vendors' => $vendors]);
    }

    public function store(): RedirectResponse
    {
        $validated = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'ecosystem' => ['required', Rule::enum(Ecosystem::class)],
        ]);

        ScanVendorJob::dispatch($validated['name'], Ecosystem::from($validated['ecosystem']));

        return Redirect::route('admin.vendors.index')
            ->with('status', "Scan für Vendor \"{$validated['name']}\" wurde eingeplant.");
    }

    public function rescan(Vendor $vendor): RedirectResponse
    {
        ScanVendorJob::dispatch($vendor->name, $vendor->ecosystem);

        return Redirect::route('admin.vendors.index')
            ->with('status', "Neuer Scan für \"{$vendor->name}\" wurde eingeplant.");
    }
}
