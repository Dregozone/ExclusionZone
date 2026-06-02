<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cities', 'lat')) {
            Schema::table('cities', function (Blueprint $table): void {
                $table->decimal('lat', 8, 5)->nullable()->after('baseline_loot_tier');
                $table->decimal('lng', 8, 5)->nullable()->after('lat');
            });
        }

        $coords = [
            1 => [51.40700,  30.05400],
            2 => [50.45000,  30.52300],
            3 => [52.22977,  21.01178],
            4 => [54.35205,  18.64637],
            5 => [42.33143, -83.04575],
            6 => [47.60621, -122.33207],
            7 => [35.68950,  139.69171],
            8 => [43.06850,  141.35069],
            9 => [-22.90278, -43.17167],
            10 => [-3.10194,  -60.02500],
            11 => [-26.20227,  28.04363],
            12 => [-33.92584,  18.42322],
        ];

        foreach ($coords as $id => [$lat, $lng]) {
            DB::table('cities')->where('id', $id)->update(['lat' => $lat, 'lng' => $lng]);
        }
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table): void {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
