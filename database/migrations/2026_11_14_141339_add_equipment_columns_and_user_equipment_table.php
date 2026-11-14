<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Items can be worn on the head, body, feet or held in either arm. Worn items speed up one skill's
 * work, ease hunger and fatigue, boost healing and turn aside radiation. Each survivor wears one
 * item per slot, taken out of their pack while it is worn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->string('equip_slot', 16)->nullable();
            $table->string('bonus_skill_key')->nullable();
            $table->unsignedTinyInteger('work_speed_bonus_percent')->default(0);
            $table->unsignedTinyInteger('hunger_reduction_percent')->default(0);
            $table->unsignedTinyInteger('fatigue_reduction_percent')->default(0);
            $table->unsignedTinyInteger('healing_bonus_percent')->default(0);
            $table->unsignedTinyInteger('radiation_protection_percent')->default(0);
        });

        Schema::create('user_equipment', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slot', 16);
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_equipment');

        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn([
                'equip_slot',
                'bonus_skill_key',
                'work_speed_bonus_percent',
                'hunger_reduction_percent',
                'fatigue_reduction_percent',
                'healing_bonus_percent',
                'radiation_protection_percent',
            ]);
        });
    }
};
