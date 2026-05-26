<?php

use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameActionController;
use App\Http\Controllers\ModerationController;
use App\Livewire\Admin\MovePlayer;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::landing.page')->name('home');
Route::livewire('/landing', 'pages::landing.page')->name('landing');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::livewire('admin/move-player', MovePlayer::class)->name('admin.move-player');
    Route::post('dashboard/travel', [GameActionController::class, 'travel'])->name('travel.store');
    Route::post('dashboard/actions', [GameActionController::class, 'performAction'])->name('city-action.store');
    Route::post('dashboard/hooks/{feature}', [GameActionController::class, 'visitHook'])->name('feature-hook.store');
    Route::post('dashboard/cosmetics', [GameActionController::class, 'equipCosmetic'])->name('cosmetics.store');
    Route::post('dashboard/moderation/mutes', [ModerationController::class, 'store'])->name('moderation.mutes.store');
    Route::post('dashboard/admin/roles', [AdminRoleController::class, 'update'])->name('admin.roles.update');
});

require __DIR__.'/settings.php';
