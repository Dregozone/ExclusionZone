<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quests', function (Blueprint $table): void {
            $table->string('quest_type')->default('job')->after('is_active');
            $table->unsignedSmallInteger('sequence_order')->nullable()->after('quest_type');
            $table->foreignId('prerequisite_quest_id')->nullable()->after('sequence_order')->constrained('quests')->nullOnDelete();
            $table->boolean('is_repeatable')->default(false)->after('prerequisite_quest_id');
        });

        Schema::table('quest_steps', function (Blueprint $table): void {
            $table->json('requirement_variants')->nullable()->after('consumes_item');
        });

        Schema::table('user_quests', function (Blueprint $table): void {
            $table->unsignedSmallInteger('completion_count')->default(0)->after('completed_at');
            $table->json('active_requirements')->nullable()->after('completion_count');
        });
    }

    public function down(): void
    {
        Schema::table('user_quests', function (Blueprint $table): void {
            $table->dropColumn(['completion_count', 'active_requirements']);
        });

        Schema::table('quest_steps', function (Blueprint $table): void {
            $table->dropColumn('requirement_variants');
        });

        Schema::table('quests', function (Blueprint $table): void {
            $table->dropForeign(['prerequisite_quest_id']);
            $table->dropColumn(['quest_type', 'sequence_order', 'prerequisite_quest_id', 'is_repeatable']);
        });
    }
};
