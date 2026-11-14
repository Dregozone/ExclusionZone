<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remember what the survivor last saw on the radio and the trade board, so the PDA can raise an
 * alert for anything newer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('radio_seen_message_id')->nullable();
            $table->timestamp('trades_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['radio_seen_message_id', 'trades_seen_at']);
        });
    }
};
