<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trouble can greet a survivor arriving in a city: a city visit log tells first arrivals from
 * returns, trouble types describe who causes it, and an encounter holds the survivor at the city
 * gate until they fight, buy their way in or run. Running back is a travel leg that cannot be canceled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'city_id']);
        });

        Schema::create('trouble_types', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('skill_key');
            $table->unsignedTinyInteger('fighter_level');
            $table->unsignedTinyInteger('damage_min');
            $table->unsignedTinyInteger('damage_max');
            $table->unsignedSmallInteger('xp_reward');
            $table->foreignId('loot_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->unsignedSmallInteger('loot_quantity')->default(0);
            $table->unsignedSmallInteger('demand_value');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trouble_encounters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trouble_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->json('queued_city_ids')->nullable();
            $table->timestamps();
        });

        Schema::table('user_works', function (Blueprint $table): void {
            $table->boolean('is_retreat')->default(false)->after('queued_city_ids');
        });

        $now = now();

        DB::table('user_locations')->whereNotNull('city_id')->orderBy('user_id')->each(function (object $location) use ($now): void {
            DB::table('city_visits')->insertOrIgnore([
                'user_id' => $location->user_id,
                'city_id' => $location->city_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('user_works', function (Blueprint $table): void {
            $table->dropColumn('is_retreat');
        });

        Schema::dropIfExists('trouble_encounters');
        Schema::dropIfExists('trouble_types');
        Schema::dropIfExists('city_visits');
    }
};
