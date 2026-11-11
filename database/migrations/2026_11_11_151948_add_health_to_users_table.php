<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Survivors now have health. Starving or working while spent wears it down, food builds it back,
 * and the death report holds the story of the survivor's last collapse until they read it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('health')->default(100);
            $table->text('death_report')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['health', 'death_report']);
        });
    }
};
