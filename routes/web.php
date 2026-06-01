<?php

use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameActionController;
use App\Http\Controllers\ModerationController;
use App\Livewire\Admin\ChangeUserRole;
use App\Livewire\Admin\ManageCityActions;
use App\Livewire\Admin\ManageCountries;
use App\Livewire\Admin\ManageItems;
use App\Livewire\Admin\ManageLocations;
use App\Livewire\Admin\ManageSkills;
use App\Livewire\Admin\MovePlayer;
use App\Livewire\WorldMap;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::landing.page')->name('home');
Route::livewire('/landing', 'pages::landing.page')->name('landing');
Route::livewire('/world-map', WorldMap::class)->name('world-map');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::post('dashboard/travel', [GameActionController::class, 'travel'])->name('travel.store');
    Route::post('dashboard/actions', [GameActionController::class, 'performAction'])->name('city-action.store');
    Route::post('dashboard/work/complete', [GameActionController::class, 'completeWork'])->name('work.complete');
    Route::post('dashboard/work/cancel', [GameActionController::class, 'cancelWork'])->name('work.cancel');
    Route::post('dashboard/hooks/{feature}', [GameActionController::class, 'visitHook'])->name('feature-hook.store');
    Route::post('dashboard/cosmetics', [GameActionController::class, 'equipCosmetic'])->name('cosmetics.store');
    Route::post('dashboard/moderation/mutes', [ModerationController::class, 'store'])->name('moderation.mutes.store');

    Route::middleware('admin')->group(function () {
        Route::livewire('admin/change-user-role', ChangeUserRole::class)->name('admin.change-user-role');
        Route::livewire('admin/move-player', MovePlayer::class)->name('admin.move-player');
        Route::livewire('admin/locations', ManageLocations::class)->name('admin.locations');
        Route::livewire('admin/countries', ManageCountries::class)->name('admin.countries');
        Route::livewire('admin/city-actions', ManageCityActions::class)->name('admin.city-actions');
        Route::livewire('admin/items', ManageItems::class)->name('admin.items');
        Route::livewire('admin/skills', ManageSkills::class)->name('admin.skills');
        Route::post('dashboard/admin/roles', [AdminRoleController::class, 'update'])->name('admin.roles.update');
    });
});

require __DIR__.'/settings.php';
