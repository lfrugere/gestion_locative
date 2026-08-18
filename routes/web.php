<?php

use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NoteController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyRoomController;
use App\Http\Controllers\Admin\SystemCheckController;
use App\Http\Controllers\Admin\TenantController;
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
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/media/{media}/download', [MediaController::class, 'download'])
            ->name('media.download');

        Route::middleware('permission:manage system')->get('/configuration', SystemCheckController::class)
            ->name('system-checks.index');

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
            Route::post('/buildings/{building}/media', [MediaController::class, 'storeBuilding'])
                ->whereNumber('building')
                ->name('buildings.media.store');
            Route::post('/buildings/{building}/notes', [NoteController::class, 'storeBuilding'])
                ->whereNumber('building')
                ->name('buildings.notes.store');
        });

        Route::middleware('permission:view properties')->group(function () {
            Route::get('/properties', [PropertyController::class, 'index'])
                ->name('properties.index');
            Route::get('/properties/{property}', [PropertyController::class, 'show'])
                ->whereNumber('property')
                ->name('properties.show');
            Route::get('/properties/{property}/rooms/{room}', [PropertyRoomController::class, 'show'])
                ->whereNumber(['property', 'room'])
                ->name('property-rooms.show');
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
            Route::post('/properties/{property}/media', [MediaController::class, 'storeProperty'])
                ->whereNumber('property')
                ->name('properties.media.store');
            Route::post('/properties/{property}/notes', [NoteController::class, 'storeProperty'])
                ->whereNumber('property')
                ->name('properties.notes.store');
            Route::get('/properties/{property}/rooms/create', [PropertyRoomController::class, 'create'])
                ->whereNumber('property')
                ->name('property-rooms.create');
            Route::post('/properties/{property}/rooms', [PropertyRoomController::class, 'store'])
                ->whereNumber('property')
                ->name('property-rooms.store');
            Route::get('/properties/{property}/rooms/{room}/edit', [PropertyRoomController::class, 'edit'])
                ->whereNumber(['property', 'room'])
                ->name('property-rooms.edit');
            Route::put('/properties/{property}/rooms/{room}', [PropertyRoomController::class, 'update'])
                ->whereNumber(['property', 'room'])
                ->name('property-rooms.update');
            Route::delete('/properties/{property}/rooms/{room}', [PropertyRoomController::class, 'destroy'])
                ->whereNumber(['property', 'room'])
                ->name('property-rooms.destroy');
            Route::post('/properties/{property}/rooms/{room}/media', [MediaController::class, 'storePropertyRoom'])
                ->whereNumber(['property', 'room'])
                ->name('property-rooms.media.store');
            Route::post('/properties/{property}/rooms/{room}/notes', [NoteController::class, 'storePropertyRoom'])
                ->whereNumber(['property', 'room'])
                ->name('property-rooms.notes.store');
        });

        Route::middleware('permission:view tenants')->group(function () {
            Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
            Route::get('/tenants/{tenant}', [TenantController::class, 'show'])
                ->whereNumber('tenant')
                ->name('tenants.show');
        });

        Route::middleware('permission:manage tenants')->group(function () {
            Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
            Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
            Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])
                ->whereNumber('tenant')
                ->name('tenants.edit');
            Route::put('/tenants/{tenant}', [TenantController::class, 'update'])
                ->whereNumber('tenant')
                ->name('tenants.update');
            Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])
                ->whereNumber('tenant')
                ->name('tenants.destroy');
            Route::post('/tenants/{tenant}/media', [MediaController::class, 'storeTenant'])
                ->whereNumber('tenant')
                ->name('tenants.media.store');
            Route::post('/tenants/{tenant}/notes', [NoteController::class, 'storeTenant'])
                ->whereNumber('tenant')
                ->name('tenants.notes.store');
        });

        Route::middleware('permission:manage buildings|manage properties|manage tenants')->group(function () {
            Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
            Route::post('/media/{media}/primary', [MediaController::class, 'setPrimary'])->name('media.primary');
            Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
            Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
            Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        });
    });
