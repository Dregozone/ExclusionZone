<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Survivors grow hungry and tired as they work and travel, which slows their timers.
 * City actions say how much each cycle costs, and food says how much it gives back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('hunger')->default(0);
            $table->unsignedTinyInteger('fatigue')->default(0);
            $table->timestamp('rested_at')->nullable();
        });

        Schema::table('city_actions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('hunger_cost')->default(2)->after('base_duration_seconds');
            $table->unsignedTinyInteger('fatigue_cost')->default(2)->after('hunger_cost');
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->unsignedTinyInteger('hunger_restored')->default(0);
            $table->unsignedTinyInteger('fatigue_restored')->default(0);
            $table->unsignedTinyInteger('health_restored')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn(['hunger_restored', 'fatigue_restored', 'health_restored']);
        });

        Schema::table('city_actions', function (Blueprint $table): void {
            $table->dropColumn(['hunger_cost', 'fatigue_cost']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['hunger', 'fatigue', 'rested_at']);
        });
    }
};
