<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('work_type');
            $table->foreignId('city_action_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('skill_key')->nullable();
            $table->unsignedSmallInteger('duration_seconds');
            $table->timestamp('available_at');
            $table->timestamps();

            $table->index(['user_id', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_works');
    }
};
