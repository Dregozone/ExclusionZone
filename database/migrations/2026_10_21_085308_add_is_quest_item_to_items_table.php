<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Story items handed out by StoryItemsSeeder; they drive quests, so they cannot be traded.
     *
     * @var array<int, string>
     */
    private const STORY_ITEM_KEYS = [
        'state_archives',
        'server_cores',
        'signal_booster',
        'dawnwatch_document',
        'cold_war_dossier',
        'courier_token',
        'encrypted_keycard',
        'arbiter_core_fragment',
    ];

    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_quest_item')->default(false)->after('rarity');
        });

        DB::table('items')->whereIn('key', self::STORY_ITEM_KEYS)->update(['is_quest_item' => true]);
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('is_quest_item');
        });
    }
};
