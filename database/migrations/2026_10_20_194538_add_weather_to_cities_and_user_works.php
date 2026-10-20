<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->string('weather')->default('clear')->after('trouble_chance_pct');
        });

        Schema::table('user_works', function (Blueprint $table) {
            $table->string('weather')->default('clear')->after('duration_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('weather');
        });

        Schema::table('user_works', function (Blueprint $table) {
            $table->dropColumn('weather');
        });
    }
};
