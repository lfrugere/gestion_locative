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
        });

        Route::middleware('permission:manage buildings')->group(function () {
            Route::get('/buildings/create', [BuildingController::class, 'create'])
                ->name('buildings.create');
            Route::post('/buildings', [BuildingController::class, 'store'])
                ->name('buildings.store');
        });

        Route::middleware('permission:view properties')->group(function () {
            Route::get('/properties', [PropertyController::class, 'index'])
                ->name('properties.index');
        });

        Route::middleware('permission:manage properties')->group(function () {
            Route::get('/properties/create', [PropertyController::class, 'create'])
                ->name('properties.create');
            Route::post('/properties', [PropertyController::class, 'store'])
                ->name('properties.store');
        });
    });
