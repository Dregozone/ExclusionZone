<?php

use App\Http\Controllers\DailyStatsController;
use App\Http\Middleware\AuthenticateStatsToken;
use Illuminate\Support\Facades\Route;

Route::get('/stats/daily', DailyStatsController::class)
    ->middleware([AuthenticateStatsToken::class, 'throttle:30,1'])
    ->name('stats.daily');
