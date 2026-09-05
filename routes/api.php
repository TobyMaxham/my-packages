<?php

use App\Http\Controllers\Api\Admin\PackageExportController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/packages', [PackageController::class, 'index'])->name('api.packages.index');
Route::get('/packages/{package}', [PackageController::class, 'show'])->name('api.packages.show');
Route::get('/vendors', [VendorController::class, 'index'])->name('api.vendors.index');
Route::get('/stats', [StatsController::class, 'index'])->name('api.stats.index');

Route::get('/admin/packages/export', [PackageExportController::class, 'index'])
    ->middleware('auth:sanctum')
    ->name('api.admin.packages.export');
