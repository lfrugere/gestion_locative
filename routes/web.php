<?php

use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NoteController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyRoomController;
use App\Http\Controllers\Admin\SystemCheckController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('portal')
        : redirect()->route('login');
});

Route::middleware(['auth', 'permission:access admin'])
    ->group(function () {
        Route::get('/portal', function () {
            return auth()->user()->can('view properties')
                ? redirect()->route('admin-locative')
                : redirect()->route('gestion-locative');
        })->name('portal');

        Route::get('/media/{media}/download', [MediaController::class, 'download'])
            ->name('media.download');

        Route::view('/gestion-locative', 'menus.gestion-locative')->name('gestion-locative');
        Route::view('/mes-contrats', 'menus.mes-contrats')->name('mes-contrats');
        Route::view('/administrations', 'menus.administrations')
            ->middleware('permission:manage system|manage users')
            ->name('administrations');

        Route::middleware('permission:view properties')->group(function () {
            Route::get('/mes-biens', [PortfolioController::class, 'myProperties'])->name('mes-biens');
            Route::get('/mes-biens/{property}', [PortfolioController::class, 'showProperty'])
                ->whereNumber('property')
                ->name('mes-biens.show');
            Route::get('/admin-locative', DashboardController::class)->name('admin-locative');
        });

        Route::view('/mes-locataires', 'menus.mes-locataires')
            ->middleware('permission:view tenants')
            ->name('mes-locataires');

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
        });

        Route::middleware('permission:view bank accounts')->group(function () {
            Route::get('/bank-accounts', [BankAccountController::class, 'index'])
                ->name('bank-accounts.index');
            Route::get('/bank-accounts/{bankAccount}', [BankAccountController::class, 'show'])
                ->whereNumber('bankAccount')
                ->name('bank-accounts.show');
        });

        Route::middleware('permission:manage bank accounts')->group(function () {
            Route::get('/bank-accounts/create', [BankAccountController::class, 'create'])
                ->name('bank-accounts.create');
            Route::post('/bank-accounts', [BankAccountController::class, 'store'])
                ->name('bank-accounts.store');
            Route::get('/bank-accounts/{bankAccount}/edit', [BankAccountController::class, 'edit'])
                ->whereNumber('bankAccount')
                ->name('bank-accounts.edit');
            Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])
                ->whereNumber('bankAccount')
                ->name('bank-accounts.update');
            Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])
                ->whereNumber('bankAccount')
                ->name('bank-accounts.destroy');
            Route::post('/bank-accounts/{bankAccount}/transactions', [BankTransactionController::class, 'store'])
                ->whereNumber('bankAccount')
                ->name('bank-accounts.transactions.store');
            Route::delete('/bank-accounts/{bankAccount}/transactions/{transaction}', [BankTransactionController::class, 'destroy'])
                ->whereNumber(['bankAccount', 'transaction'])
                ->name('bank-accounts.transactions.destroy');

            Route::get('/bank-accounts/{bankAccount}/reconciliations/create', [BankReconciliationController::class, 'create'])
                ->whereNumber('bankAccount')
                ->name('bank-accounts.reconciliations.create');
            Route::post('/bank-accounts/{bankAccount}/reconciliations', [BankReconciliationController::class, 'store'])
                ->whereNumber('bankAccount')
                ->name('bank-accounts.reconciliations.store');
            Route::get('/bank-accounts/{bankAccount}/reconciliations/{reconciliation}/edit', [BankReconciliationController::class, 'edit'])
                ->whereNumber(['bankAccount', 'reconciliation'])
                ->name('bank-accounts.reconciliations.edit');
            Route::patch('/bank-accounts/{bankAccount}/reconciliations/{reconciliation}', [BankReconciliationController::class, 'update'])
                ->whereNumber(['bankAccount', 'reconciliation'])
                ->name('bank-accounts.reconciliations.update');
            Route::post('/bank-accounts/{bankAccount}/reconciliations/{reconciliation}/close', [BankReconciliationController::class, 'close'])
                ->whereNumber(['bankAccount', 'reconciliation'])
                ->name('bank-accounts.reconciliations.close');
            Route::delete('/bank-accounts/{bankAccount}/reconciliations/{reconciliation}', [BankReconciliationController::class, 'destroy'])
                ->whereNumber(['bankAccount', 'reconciliation'])
                ->name('bank-accounts.reconciliations.destroy');
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
            Route::put('/properties/{property}/managers', [PropertyController::class, 'updateManagers'])
                ->whereNumber('property')
                ->name('properties.managers.update');
            Route::post('/properties/{property}/media', [MediaController::class, 'storeProperty'])
                ->whereNumber('property')
                ->name('properties.media.store');
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
        });

        Route::middleware('permission:manage invoices')->group(function () {
            Route::post('/properties/{property}/invoices', [InvoiceController::class, 'store'])
                ->whereNumber('property')
                ->name('properties.invoices.store');
            Route::delete('/properties/{property}/invoices/{invoice}', [InvoiceController::class, 'destroy'])
                ->whereNumber(['property', 'invoice'])
                ->name('properties.invoices.destroy');
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
        });

        Route::middleware('permission:manage buildings|manage properties|manage tenants')->group(function () {
            Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
            Route::post('/media/{media}/primary', [MediaController::class, 'setPrimary'])->name('media.primary');
            Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
        });

        Route::middleware('permission:manage notes')->group(function () {
            Route::post('/buildings/{building}/notes', [NoteController::class, 'storeBuilding'])
                ->whereNumber('building')
                ->name('buildings.notes.store');
            Route::post('/properties/{property}/notes', [NoteController::class, 'storeProperty'])
                ->whereNumber('property')
                ->name('properties.notes.store');
            Route::post('/properties/{property}/rooms/{room}/notes', [NoteController::class, 'storePropertyRoom'])
                ->whereNumber(['property', 'room'])
                ->name('property-rooms.notes.store');
            Route::post('/tenants/{tenant}/notes', [NoteController::class, 'storeTenant'])
                ->whereNumber('tenant')
                ->name('tenants.notes.store');
            Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
            Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
        });

        Route::middleware('permission:manage users')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])
                ->whereNumber('user')
                ->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])
                ->whereNumber('user')
                ->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])
                ->whereNumber('user')
                ->name('users.destroy');
        });
    });
