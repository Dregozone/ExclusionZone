<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blows can miss. An enemy's accuracy is its chance to land a blow and its defence lowers the survivor's
 * chance to land one. Armour lowers an enemy's chance to land a blow by its defence bonus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enemies', function (Blueprint $table): void {
            $table->unsignedTinyInteger('accuracy')->default(60);
            $table->unsignedTinyInteger('defence')->default(0);
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->unsignedTinyInteger('defence_bonus')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('defence_bonus');
        });

        Schema::table('enemies', function (Blueprint $table): void {
            $table->dropColumn(['accuracy', 'defence']);
        });
    }
};
