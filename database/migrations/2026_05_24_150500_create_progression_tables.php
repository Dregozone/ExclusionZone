<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('display_name');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('skill_level_rules', function (Blueprint $table) {
            $table->id();
            $table->string('tier')->unique();
            $table->unsignedTinyInteger('level_min');
            $table->unsignedTinyInteger('level_max')->nullable();
            $table->string('unlock_examples');
            $table->timestamps();
        });

        Schema::create('premium_cosmetics', function (Blueprint $table) {
            $table->id();
            $table->string('cosmetic_type');
            $table->string('name');
            $table->string('gameplay_bonus')->default('none');
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedInteger('xp')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'skill_id']);
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'item_id']);
        });

        Schema::create('user_cosmetic_loadouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('outfit_skin_id')->nullable()->constrained('premium_cosmetics')->nullOnDelete();
            $table->foreignId('ui_theme_id')->nullable()->constrained('premium_cosmetics')->nullOnDelete();
            $table->foreignId('profile_flair_id')->nullable()->constrained('premium_cosmetics')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('user_mutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moderator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_mutes');
        Schema::dropIfExists('user_cosmetic_loadouts');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('items');
        Schema::dropIfExists('premium_cosmetics');
        Schema::dropIfExists('skill_level_rules');
        Schema::dropIfExists('skills');
    }
};
