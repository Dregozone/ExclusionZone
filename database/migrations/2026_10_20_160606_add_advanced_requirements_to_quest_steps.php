<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quest_steps', function (Blueprint $table): void {
            $table->json('required_items')->nullable()->after('required_item_quantity');
            $table->foreignId('required_city_action_id')->nullable()->after('requirement_variants')->constrained('city_actions')->nullOnDelete();
            $table->unsignedSmallInteger('required_action_count')->nullable()->after('required_city_action_id');
        });

        Schema::table('user_quests', function (Blueprint $table): void {
            $table->unsignedSmallInteger('step_action_progress')->default(0)->after('current_step_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_quests', function (Blueprint $table): void {
            $table->dropColumn('step_action_progress');
        });

        Schema::table('quest_steps', function (Blueprint $table): void {
            $table->dropForeign(['required_city_action_id']);
            $table->dropColumn(['required_items', 'required_city_action_id', 'required_action_count']);
        });
    }
};
