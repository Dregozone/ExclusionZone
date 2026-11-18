<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The items a trade post's bazaar sells, chosen per city on the admin locations page. A trade post
 * with nothing listed here sells the default stock from config/shop.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_bazaar_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['city_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_bazaar_items');
    }
};
