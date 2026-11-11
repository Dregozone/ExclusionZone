<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Zone itself now hurts: irradiated cities cost health for every cycle worked or leg arrived there,
 * and city actions can go wrong. After dying, a survivor needs time to recover before working again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table): void {
            $table->unsignedTinyInteger('radiation_damage')->default(0);
        });

        Schema::table('city_actions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('hazard_chance_pct')->default(0);
            $table->unsignedTinyInteger('hazard_damage')->default(0);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('recovering_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('recovering_until');
        });

        Schema::table('city_actions', function (Blueprint $table): void {
            $table->dropColumn(['hazard_chance_pct', 'hazard_damage']);
        });

        Schema::table('cities', function (Blueprint $table): void {
            $table->dropColumn('radiation_damage');
        });
    }
};
