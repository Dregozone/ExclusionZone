<?php

use Illuminate\Support\Facades\DB;

test('new countries are seeded with correct continents', function () {
    expect(DB::table('countries')->where('country', 'United Kingdom')->value('continent'))->toBe('Europe');
    expect(DB::table('countries')->where('country', 'Ireland')->value('continent'))->toBe('Europe');
    expect(DB::table('countries')->where('country', 'Russia')->value('continent'))->toBe('Europe');
    expect(DB::table('countries')->where('country', 'China')->value('continent'))->toBe('Asia');
    expect(DB::table('countries')->where('country', 'Australia')->value('continent'))->toBe('Oceania');
    expect(DB::table('countries')->where('country', 'Denmark')->value('continent'))->toBe('Europe');
    expect(DB::table('countries')->where('country', 'Norway')->value('continent'))->toBe('Europe');
    expect(DB::table('countries')->where('country', 'Sweden')->value('continent'))->toBe('Europe');
    expect(DB::table('countries')->where('country', 'Finland')->value('continent'))->toBe('Europe');
});

test('new cities are seeded with coordinates and correct countries', function () {
    $cities = DB::table('cities')
        ->join('countries', 'cities.country_id', '=', 'countries.id')
        ->whereIn('city', ['London', 'Edinburgh', 'Dublin', 'Moscow', 'St Petersburg', 'Beijing', 'Sydney'])
        ->get(['city', 'lat', 'lng', 'continent']);

    expect($cities)->toHaveCount(7);

    $byName = $cities->keyBy('city');

    expect($byName['London']->lat)->toBeFloat()->and($byName['London']->lng)->toBeFloat();
    expect($byName['London']->continent)->toBe('Europe');

    expect($byName['Edinburgh']->continent)->toBe('Europe');
    expect($byName['Dublin']->continent)->toBe('Europe');
    expect($byName['Moscow']->continent)->toBe('Europe');
    expect($byName['St Petersburg']->continent)->toBe('Europe');
    expect($byName['Beijing']->continent)->toBe('Asia');
    expect($byName['Sydney']->continent)->toBe('Oceania');
});

test('new city connections are correctly established', function () {
    $london = DB::table('cities')->where('city', 'London')->value('id');
    $edinburgh = DB::table('cities')->where('city', 'Edinburgh')->value('id');
    $dublin = DB::table('cities')->where('city', 'Dublin')->value('id');
    $moscow = DB::table('cities')->where('city', 'Moscow')->value('id');
    $stpete = DB::table('cities')->where('city', 'St Petersburg')->value('id');
    $warsaw = DB::table('cities')->where('city', 'Warsaw')->value('id');
    $gdansk = DB::table('cities')->where('city', 'Gdansk')->value('id');
    $beijing = DB::table('cities')->where('city', 'Beijing')->value('id');
    $tokyo = DB::table('cities')->where('city', 'Tokyo')->value('id');
    $sydney = DB::table('cities')->where('city', 'Sydney')->value('id');
    $capeTown = DB::table('cities')->where('city', 'Cape Town')->value('id');

    $hasConnection = fn ($from, $to) => DB::table('city_connections')
        ->where('city_id', $from)
        ->where('neighbor_city_id', $to)
        ->exists();

    expect($hasConnection($london, $edinburgh))->toBeTrue();
    expect($hasConnection($london, $dublin))->toBeTrue();
    expect($hasConnection($london, $warsaw))->toBeTrue();
    expect($hasConnection($gdansk, $stpete))->toBeTrue();
    expect($hasConnection($stpete, $moscow))->toBeTrue();
    expect($hasConnection($moscow, $warsaw))->toBeTrue();
    expect($hasConnection($beijing, $tokyo))->toBeTrue();
    expect($hasConnection($sydney, $capeTown))->toBeTrue();
    expect($hasConnection($sydney, $beijing))->toBeTrue();
});

test('new cities have three actions each', function () {
    $cities = DB::table('cities')
        ->whereIn('city', [
            'London', 'Edinburgh', 'Dublin', 'Moscow', 'St Petersburg', 'Beijing', 'Sydney',
            'Copenhagen', 'Oslo', 'Stockholm', 'Helsinki',
        ])
        ->get(['id', 'city']);

    foreach ($cities as $city) {
        $count = DB::table('city_actions')->where('city_id', $city->id)->count();
        expect($count)->toBe(3, "Expected 3 actions for {$city->city}, got {$count}");
    }
});

test('scandinavian cities are seeded with coordinates', function () {
    $cities = DB::table('cities')
        ->join('countries', 'cities.country_id', '=', 'countries.id')
        ->whereIn('city', ['Copenhagen', 'Oslo', 'Stockholm', 'Helsinki'])
        ->get(['city', 'lat', 'lng', 'continent']);

    expect($cities)->toHaveCount(4);

    foreach ($cities as $city) {
        expect($city->lat)->not->toBeNull();
        expect($city->lng)->not->toBeNull();
        expect($city->continent)->toBe('Europe');
    }
});

test('scandinavian city connections form correct chain', function () {
    $gdansk = DB::table('cities')->where('city', 'Gdansk')->value('id');
    $copenhagen = DB::table('cities')->where('city', 'Copenhagen')->value('id');
    $oslo = DB::table('cities')->where('city', 'Oslo')->value('id');
    $stockholm = DB::table('cities')->where('city', 'Stockholm')->value('id');
    $helsinki = DB::table('cities')->where('city', 'Helsinki')->value('id');
    $stpete = DB::table('cities')->where('city', 'St Petersburg')->value('id');

    $hasConnection = fn ($from, $to) => DB::table('city_connections')
        ->where('city_id', $from)
        ->where('neighbor_city_id', $to)
        ->exists();

    expect($hasConnection($gdansk, $copenhagen))->toBeTrue();
    expect($hasConnection($copenhagen, $oslo))->toBeTrue();
    expect($hasConnection($oslo, $stockholm))->toBeTrue();
    expect($hasConnection($stockholm, $helsinki))->toBeTrue();
    expect($hasConnection($helsinki, $stpete))->toBeTrue();
    expect($hasConnection($stockholm, $gdansk))->toBeTrue();
});
