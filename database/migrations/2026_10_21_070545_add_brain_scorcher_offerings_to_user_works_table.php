<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_works', function (Blueprint $table) {
            $table->foreignId('offered_item_id')->nullable()->after('destination_city_id')->constrained('items')->nullOnDelete();
            $table->unsignedInteger('offered_quantity')->nullable()->after('offered_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_works', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offered_item_id');
            $table->dropColumn('offered_quantity');
        });
    }
};
