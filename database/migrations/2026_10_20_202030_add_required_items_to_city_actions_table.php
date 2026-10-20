<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('city_actions', function (Blueprint $table): void {
            $table->json('required_items')->nullable()->after('reward_profile');
        });
    }

    public function down(): void
    {
        Schema::table('city_actions', function (Blueprint $table): void {
            $table->dropColumn('required_items');
        });
    }
};
