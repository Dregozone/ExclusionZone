<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_works', function (Blueprint $table) {
            $table->unsignedInteger('cycles_completed')->default(0)->after('available_at');
            $table->unsignedInteger('xp_earned')->default(0)->after('cycles_completed');
            $table->text('last_cycle_summary')->nullable()->after('xp_earned');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('last_work_report')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_works', function (Blueprint $table) {
            $table->dropColumn(['cycles_completed', 'xp_earned', 'last_cycle_summary']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_work_report');
        });
    }
};
