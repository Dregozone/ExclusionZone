<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->foreignId('reward_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->unsignedSmallInteger('reward_item_quantity')->default(1);
            $table->foreignId('reward_skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->unsignedInteger('reward_xp_amount')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quest_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->string('person_of_interest');
            $table->string('action_label');
            $table->text('interaction_text');
            $table->foreignId('required_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->unsignedSmallInteger('required_item_quantity')->default(1);
            $table->boolean('consumes_item')->default(false);
            $table->timestamps();

            $table->index(['quest_id', 'step_order']);
        });

        Schema::create('user_quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quest_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('current_step_index')->default(0);
            $table->string('status')->default('active');
            $table->json('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'quest_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_quests');
        Schema::dropIfExists('quest_steps');
        Schema::dropIfExists('quests');
    }
};
