<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One key trading hub per continent.
     *
     * @var array<int, string>
     */
    private const TRADE_POST_CITIES = [
        'Warsaw',
        'Seattle',
        'Rio de Janeiro',
        'Tokyo',
        'Johannesburg',
        'Sydney',
    ];

    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->boolean('has_trade_post')->default(false)->after('baseline_loot_tier');
        });

        DB::table('cities')->whereIn('city', self::TRADE_POST_CITIES)->update(['has_trade_post' => true]);
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('has_trade_post');
        });
    }
};
