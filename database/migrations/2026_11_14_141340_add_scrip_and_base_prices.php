<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scrip is the Zone's currency. Every item has a base price that trade post bazaars buy and sell
 * around, and every survivor starts with a little scrip in their pocket.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table): void {
            $table->unsignedInteger('base_price')->default(0);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('scrip')->default(25);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('scrip');
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('base_price');
        });
    }
};
