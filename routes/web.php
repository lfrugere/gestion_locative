<?php

use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\PropertyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'permission:access admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');

        Route::middleware('permission:view buildings')->group(function () {
            Route::get('/buildings', [BuildingController::class, 'index'])
                ->name('buildings.index');
            Route::get('/buildings/{building}', [BuildingController::class, 'show'])
                ->whereNumber('building')
                ->name('buildings.show');
        });

        Route::middleware('permission:manage buildings')->group(function () {
            Route::get('/buildings/create', [BuildingController::class, 'create'])
                ->name('buildings.create');
            Route::post('/buildings', [BuildingController::class, 'store'])
                ->name('buildings.store');
            Route::get('/buildings/{building}/edit', [BuildingController::class, 'edit'])
                ->whereNumber('building')
                ->name('buildings.edit');
            Route::put('/buildings/{building}', [BuildingController::class, 'update'])
                ->whereNumber('building')
                ->name('buildings.update');
            Route::delete('/buildings/{building}', [BuildingController::class, 'destroy'])
                ->whereNumber('building')
                ->name('buildings.destroy');
        });

        Route::middleware('permission:view properties')->group(function () {
            Route::get('/properties', [PropertyController::class, 'index'])
                ->name('properties.index');
            Route::get('/properties/{property}', [PropertyController::class, 'show'])
                ->whereNumber('property')
                ->name('properties.show');
        });

        Route::middleware('permission:manage properties')->group(function () {
            Route::get('/properties/create', [PropertyController::class, 'create'])
                ->name('properties.create');
            Route::post('/properties', [PropertyController::class, 'store'])
                ->name('properties.store');
            Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])
                ->whereNumber('property')
                ->name('properties.edit');
            Route::put('/properties/{property}', [PropertyController::class, 'update'])
                ->whereNumber('property')
                ->name('properties.update');
            Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])
                ->whereNumber('property')
                ->name('properties.destroy');
        });
    });
