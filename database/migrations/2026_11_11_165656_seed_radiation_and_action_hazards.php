<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pripyat is irradiated, and the riskiest city actions can now injure the survivor doing them.
 * Low and medium risk work stays safe.
 */
return new class extends Migration
{
    /**
     * @var array<string, int>
     */
    private array $radiationDamageByCity = [
        'Pripyat' => 2,
    ];

    /**
     * @var array<string, array{hazard_chance_pct: int, hazard_damage: int}>
     */
    private array $hazardsByRiskLevel = [
        'high' => ['hazard_chance_pct' => 10, 'hazard_damage' => 6],
        'extreme' => ['hazard_chance_pct' => 20, 'hazard_damage' => 10],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach ($this->radiationDamageByCity as $city => $radiationDamage) {
                DB::table('cities')->where('city', $city)->update(['radiation_damage' => $radiationDamage, 'updated_at' => now()]);
            }

            foreach ($this->hazardsByRiskLevel as $riskLevel => $hazard) {
                DB::table('city_actions')->where('risk_level', $riskLevel)->update([...$hazard, 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        DB::table('cities')->update(['radiation_damage' => 0]);
        DB::table('city_actions')->update(['hazard_chance_pct' => 0, 'hazard_damage' => 0]);
    }
};
