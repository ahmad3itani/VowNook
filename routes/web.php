<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\SwitchWeddingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Switch the active wedding (tenant context).
    Route::post('weddings/{wedding}/switch', SwitchWeddingController::class)
        ->name('weddings.switch');

    // Admin panel.
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/settings.php';
