<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_actions', function (Blueprint $table) {
            $table->unsignedSmallInteger('base_duration_seconds')->default(30)->after('risk_level');
        });

        DB::table('city_actions')->where('risk_level', 'low')->update(['base_duration_seconds' => 10]);
        DB::table('city_actions')->where('risk_level', 'medium')->update(['base_duration_seconds' => 20]);
        DB::table('city_actions')->where('risk_level', 'high')->update(['base_duration_seconds' => 30]);
        DB::table('city_actions')->where('risk_level', 'extreme')->update(['base_duration_seconds' => 40]);
    }

    public function down(): void
    {
        Schema::table('city_actions', function (Blueprint $table) {
            $table->dropColumn('base_duration_seconds');
        });
    }
};
