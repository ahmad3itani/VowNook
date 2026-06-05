<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestGroupController;
use App\Http\Controllers\SwitchWeddingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Switch the active wedding (tenant context).
    Route::post('weddings/{wedding}/switch', SwitchWeddingController::class)
        ->name('weddings.switch');

    // Guests workspace.
    Route::get('guests', [GuestController::class, 'index'])
        ->middleware('permission:guests,read')->name('guests.index');

    Route::middleware('permission:guests,write')->group(function () {
        Route::post('guests', [GuestController::class, 'store'])->name('guests.store');
        Route::put('guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');

        Route::post('guest-groups', [GuestGroupController::class, 'store'])->name('guest-groups.store');
        Route::put('guest-groups/{group}', [GuestGroupController::class, 'update'])->name('guest-groups.update');
        Route::delete('guest-groups/{group}', [GuestGroupController::class, 'destroy'])->name('guest-groups.destroy');
    });

    // Admin panel.
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/settings.php';
