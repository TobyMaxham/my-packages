<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PackageController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->value();

        $packages = Package::query()
            ->with('vendor')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.packages.index', ['packages' => $packages, 'search' => $search]);
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'is_featured' => ['sometimes', 'boolean'],
            'featured_note' => ['nullable', 'string', 'max:500'],
        ]);

        $package->update([
            'is_featured' => $request->boolean('is_featured'),
            'featured_note' => $validated['featured_note'] ?? null,
        ]);

        return $this->backToIndex($request)->with('status', "\"{$package->name}\" wurde aktualisiert.");
    }

    /**
     * Quick one-click favorite toggle — deliberately ignores the rest of the
     * submitted form (e.g. an in-progress, unsaved featured_note edit) and
     * only flips the current is_featured value.
     */
    public function toggleFeatured(Request $request, Package $package): RedirectResponse
    {
        $package->update(['is_featured' => ! $package->is_featured]);

        $message = $package->is_featured
            ? "\"{$package->name}\" ist jetzt favorisiert."
            : "\"{$package->name}\" ist nicht mehr favorisiert.";

        return $this->backToIndex($request)->with('status', $message);
    }

    private function backToIndex(Request $request): RedirectResponse
    {
        return Redirect::route('admin.packages.index', array_filter([
            'search' => $request->input('search'),
            'page' => $request->input('page'),
        ]));
    }
}
