<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class StoryItemsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'key' => 'state_archives',
                'name' => 'State Archives',
                'description' => 'Recovered government documents from pre-war administrative facilities, containing classified operational records.',
            ],
            [
                'key' => 'server_cores',
                'name' => 'Server Cores',
                'description' => 'Functional processing units salvaged from pre-war data centres, still holding encrypted operational data.',
            ],
            [
                'key' => 'signal_booster',
                'name' => 'Signal Booster',
                'description' => 'A salvaged amplifier circuit, rewound and reinforced to extend reception range through heavy interference.',
            ],
            [
                'key' => 'dawnwatch_document',
                'name' => 'DAWNWATCH Document',
                'description' => 'A decoded transmission printout bearing the seal of a classified NATO early-warning programme.',
            ],
            [
                'key' => 'cold_war_dossier',
                'name' => 'Cold War Dossier',
                'description' => 'Intelligence files detailing the dual AI programme and the events that preceded the nuclear exchange.',
            ],
            [
                'key' => 'courier_token',
                'name' => 'Courier Token',
                'description' => 'A verification token used by underground courier networks to identify trusted carriers.',
            ],
            [
                'key' => 'encrypted_keycard',
                'name' => 'Encrypted Keycard',
                'description' => 'A high-security access card for pre-war classified installations, still carrying a valid authentication signature.',
            ],
            [
                'key' => 'arbiter_core_fragment',
                'name' => 'ARBITER Core Fragment',
                'description' => 'A crystallised memory shard extracted from the ARBITER system, holding the authentication keys for its final shutdown.',
            ],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(['key' => $item['key']], $item);
        }
    }
}
