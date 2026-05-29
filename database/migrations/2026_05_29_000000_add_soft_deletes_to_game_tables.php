<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('city_actions', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('city_actions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
