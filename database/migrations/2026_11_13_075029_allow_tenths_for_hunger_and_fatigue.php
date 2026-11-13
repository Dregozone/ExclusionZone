<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hunger and fatigue now move in tenths, and each city action cycle costs between 0.5 and 1
 * of each instead of 2 to 6, so survivors tire far more slowly.
 *
 * Existing costs keep their order: 1 becomes 0.5, each step up adds 0.1, and anything from 6 up costs 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('hunger', 4, 1)->default(0)->change();
            $table->decimal('fatigue', 4, 1)->default(0)->change();
        });

        Schema::table('city_actions', function (Blueprint $table): void {
            $table->decimal('hunger_cost', 4, 1)->default(0.6)->change();
            $table->decimal('fatigue_cost', 4, 1)->default(0.6)->change();
        });

        DB::table('city_actions')->update([
            'hunger_cost' => DB::raw($this->rescaledCost('hunger_cost')),
            'fatigue_cost' => DB::raw($this->rescaledCost('fatigue_cost')),
        ]);
    }

    public function down(): void
    {
        DB::table('city_actions')->update([
            'hunger_cost' => DB::raw($this->restoredCost('hunger_cost')),
            'fatigue_cost' => DB::raw($this->restoredCost('fatigue_cost')),
        ]);
        DB::table('users')->update([
            'hunger' => DB::raw('ROUND(hunger)'),
            'fatigue' => DB::raw('ROUND(fatigue)'),
        ]);

        Schema::table('city_actions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('hunger_cost')->default(2)->change();
            $table->unsignedTinyInteger('fatigue_cost')->default(2)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('hunger')->default(0)->change();
            $table->unsignedTinyInteger('fatigue')->default(0)->change();
        });
    }

    private function rescaledCost(string $column): string
    {
        return "CASE WHEN {$column} <= 0 THEN 0 WHEN {$column} >= 6 THEN 1 ELSE 0.5 + ({$column} - 1) * 0.1 END";
    }

    private function restoredCost(string $column): string
    {
        return "CASE WHEN {$column} <= 0 THEN 0 ELSE ROUND(({$column} - 0.5) * 10) + 1 END";
    }
};
