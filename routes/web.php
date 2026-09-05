<?php

use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
    Route::post('/vendors', [AdminVendorController::class, 'store'])->name('vendors.store');
    Route::post('/vendors/{vendor}/rescan', [AdminVendorController::class, 'rescan'])->name('vendors.rescan');

    Route::get('/packages', [AdminPackageController::class, 'index'])->name('packages.index');
    Route::post('/packages/{package}', [AdminPackageController::class, 'update'])->name('packages.update');
    Route::post('/packages/{package}/toggle-featured', [AdminPackageController::class, 'toggleFeatured'])->name('packages.toggle-featured');
});

// The Package Compatibility Dashboard is a Vue SPA (routes: /, /packages, /login).
// This catch-all serves its shell so client-side reloads don't 404; /api, /sanctum
// and /admin stay excluded for the backend routes above.
Route::view('/', 'packages.dashboard')->name('packages.dashboard.home');
Route::view('/{any}', 'packages.dashboard')
    ->where('any', '^(?!api|sanctum|admin).*$')
    ->name('packages.dashboard');
