<?php

namespace App\Http\Controllers;

use App\Models\Login;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyStatsController extends Controller
{
    private const TIMEZONE = 'Europe/London';

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $day = isset($validated['date'])
            ? now(self::TIMEZONE)->setTimezone(self::TIMEZONE)->modify($validated['date'])->startOfDay()
            : now(self::TIMEZONE)->subDay()->startOfDay();

        $start = $day->copy()->utc();
        $end = $day->copy()->endOfDay()->utc();

        $logins = Login::query()->whereBetween('created_at', [$start, $end]);

        return response()->json([
            'app' => 'Exclusion Zone',
            'date' => $day->toDateString(),
            'signups' => User::query()->whereBetween('created_at', [$start, $end])->count(),
            'logins' => [
                'total' => (clone $logins)->count(),
                'unique' => (clone $logins)->distinct()->count('user_id'),
            ],
            'users' => [
                'total' => User::query()->where('created_at', '<=', $end)->count(),
                'active_7d' => $this->activeSince($end->copy()->subDays(7), $end),
                'active_30d' => $this->activeSince($end->copy()->subDays(30), $end),
            ],
        ]);
    }

    private function activeSince($since, $until): int
    {
        return User::query()->whereBetween('last_active_at', [$since, $until])->count();
    }
}
