<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enemies wait in a city. A city action using the combat skill an enemy tests starts a fight with one of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enemies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('skill_key');
            $table->unsignedSmallInteger('health');
            $table->unsignedTinyInteger('min_damage');
            $table->unsignedTinyInteger('max_damage');
            $table->unsignedSmallInteger('xp_reward');
            $table->json('loot')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city_id', 'skill_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enemies');
    }
};
