<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Weapons hit harder by their attack bonus, and a fight in progress keeps its enemy and that
 * enemy's remaining health on the work loop, one round per cycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->unsignedTinyInteger('attack_bonus')->default(0);
        });

        Schema::table('user_works', function (Blueprint $table): void {
            $table->foreignId('enemy_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('enemy_health')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_works', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('enemy_id');
            $table->dropColumn('enemy_health');
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('attack_bonus');
        });
    }
};
