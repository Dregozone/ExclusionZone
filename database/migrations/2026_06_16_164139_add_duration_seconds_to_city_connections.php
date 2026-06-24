<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_connections', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_seconds')->default(30)->after('neighbor_city_id');
        });
    }

    public function down(): void
    {
        Schema::table('city_connections', function (Blueprint $table) {
            $table->dropColumn('duration_seconds');
        });
    }
};
