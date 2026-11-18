<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Some actions can only be done while wearing the right gear. Unlike required items, worn gear is
 * not used up. The reactor zone in Pripyat needs a gas mask and a hazmat suit worn together, and
 * the Beijing data centre needs a soldering iron in hand.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private const GATED_ACTIONS = [
        'scavenge_reactor_zone' => ['gas_mask', 'hazmat_suit'],
        'beijing_data_center_recovery' => ['soldering_iron'],
    ];

    public function up(): void
    {
        Schema::table('city_actions', function (Blueprint $table): void {
            $table->json('required_gear')->nullable()->after('required_items');
        });

        foreach (self::GATED_ACTIONS as $actionKey => $itemKeys) {
            DB::table('city_actions')
                ->where('action_key', $actionKey)
                ->update(['required_gear' => json_encode($itemKeys)]);
        }
    }

    public function down(): void
    {
        Schema::table('city_actions', function (Blueprint $table): void {
            $table->dropColumn('required_gear');
        });
    }
};
