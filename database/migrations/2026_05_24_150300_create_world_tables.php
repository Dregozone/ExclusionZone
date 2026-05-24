<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('continent');
            $table->string('country')->unique();
            $table->unsignedTinyInteger('avg_temp_c');
            $table->unsignedTinyInteger('rain_chance_pct');
            $table->unsignedTinyInteger('trouble_chance_pct');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('city')->unique();
            $table->string('biome');
            $table->unsignedTinyInteger('rain_chance_pct');
            $table->unsignedTinyInteger('trouble_chance_pct');
            $table->string('baseline_loot_tier');
            $table->timestamps();
        });

        Schema::create('city_connections', function (Blueprint $table) {
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('neighbor_city_id')->constrained('cities')->cascadeOnDelete();
            $table->primary(['city_id', 'neighbor_city_id']);
        });

        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('district')->nullable();
            $table->integer('x_coord')->nullable();
            $table->integer('y_coord')->nullable();
            $table->timestamps();
        });

        Schema::create('city_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('action_key')->unique();
            $table->string('label');
            $table->text('description');
            $table->string('skill_key');
            $table->unsignedTinyInteger('min_level')->default(1);
            $table->string('risk_level');
            $table->json('reward_profile');
            $table->timestamps();
            $table->index(['city_id', 'skill_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_actions');
        Schema::dropIfExists('user_locations');
        Schema::dropIfExists('city_connections');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
