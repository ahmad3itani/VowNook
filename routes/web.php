<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\BudgetCategoryController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestGroupController;
use App\Http\Controllers\PublicRsvpController;
use App\Http\Controllers\PublicSeatingController;
use App\Http\Controllers\SeatingController;
use App\Http\Controllers\SwitchWeddingController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Public, unauthenticated wedding RSVP site.
Route::get('w/{wedding}', [PublicRsvpController::class, 'show'])->name('public.rsvp');
Route::post('w/{wedding}/lookup', [PublicRsvpController::class, 'lookup'])->name('public.rsvp.lookup');
Route::post('w/{wedding}/respond', [PublicRsvpController::class, 'respond'])->name('public.rsvp.respond');

// Public seat finder — backs a printed QR code at the venue.
Route::get('w/{wedding}/seats', [PublicSeatingController::class, 'show'])->name('public.seats');
Route::post('w/{wedding}/seats/find', [PublicSeatingController::class, 'find'])->name('public.seats.find');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shareable public links + printable QR codes for the active wedding.
    Route::inertia('share', 'share/index')->name('share');

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

    // CSV exports (gated by each workspace's read permission).
    Route::get('exports/guests', [ExportController::class, 'guests'])
        ->middleware('permission:guests,read')->name('exports.guests');
    Route::get('exports/budget', [ExportController::class, 'budget'])
        ->middleware('permission:budget,read')->name('exports.budget');
    Route::get('exports/timeline', [ExportController::class, 'timeline'])
        ->middleware('permission:timeline,read')->name('exports.timeline');

    // Budget workspace.
    Route::get('budget', [BudgetController::class, 'index'])
        ->middleware('permission:budget,read')->name('budget.index');

    Route::middleware('permission:budget,write')->group(function () {
        Route::post('budget', [BudgetController::class, 'store'])->name('budget.store');
        Route::put('budget/{item}', [BudgetController::class, 'update'])->name('budget.update');
        Route::delete('budget/{item}', [BudgetController::class, 'destroy'])->name('budget.destroy');

        Route::post('budget-categories', [BudgetCategoryController::class, 'store'])->name('budget-categories.store');
        Route::put('budget-categories/{category}', [BudgetCategoryController::class, 'update'])->name('budget-categories.update');
        Route::delete('budget-categories/{category}', [BudgetCategoryController::class, 'destroy'])->name('budget-categories.destroy');
    });

    // Vendors workspace.
    Route::get('vendors', [VendorController::class, 'index'])
        ->middleware('permission:vendors,read')->name('vendors.index');

    Route::middleware('permission:vendors,write')->group(function () {
        Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::put('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
    });

    // Checklist workspace.
    Route::get('checklist', [ChecklistController::class, 'index'])
        ->middleware('permission:checklist,read')->name('checklist.index');

    Route::middleware('permission:checklist,write')->group(function () {
        Route::post('checklist', [ChecklistController::class, 'store'])->name('checklist.store');
        Route::put('checklist/{task}', [ChecklistController::class, 'update'])->name('checklist.update');
        Route::patch('checklist/{task}/toggle', [ChecklistController::class, 'toggle'])->name('checklist.toggle');
        Route::delete('checklist/{task}', [ChecklistController::class, 'destroy'])->name('checklist.destroy');
    });

    // Timeline workspace.
    Route::get('timeline', [TimelineController::class, 'index'])
        ->middleware('permission:timeline,read')->name('timeline.index');

    Route::middleware('permission:timeline,write')->group(function () {
        Route::post('timeline', [TimelineController::class, 'store'])->name('timeline.store');
        Route::put('timeline/{event}', [TimelineController::class, 'update'])->name('timeline.update');
        Route::delete('timeline/{event}', [TimelineController::class, 'destroy'])->name('timeline.destroy');
    });

    // Seating chart workspace.
    Route::get('seating', [SeatingController::class, 'index'])
        ->middleware('permission:seating,read')->name('seating.index');

    Route::middleware('permission:seating,write')->group(function () {
        Route::post('seating', [SeatingController::class, 'store'])->name('seating.store');
        Route::put('seating/{table}', [SeatingController::class, 'update'])->name('seating.update');
        Route::patch('seating/{table}/move', [SeatingController::class, 'move'])->name('seating.move');
        Route::delete('seating/{table}', [SeatingController::class, 'destroy'])->name('seating.destroy');
        Route::patch('seating-assign', [SeatingController::class, 'assign'])->name('seating.assign');
    });

    // Admin panel.
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/settings.php';
