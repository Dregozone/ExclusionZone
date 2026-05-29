<?php

namespace App\Livewire;

use App\Models\City;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('World Map')]
class WorldMap extends Component
{
    #[Computed]
    public function cities(): Collection
    {
        return City::query()
            ->with(['neighbors:id', 'country:id,country'])
            ->orderBy('city')
            ->get(['id', 'city', 'country_id', 'biome']);
    }

    #[Computed]
    public function currentCityId(): ?int
    {
        if (! auth()->check()) {
            return null;
        }

        /** @var User $user */
        $user = auth()->user();

        return UserLocation::query()
            ->where('user_id', $user->id)
            ->value('city_id');
    }

    /**
     * Build a JSON-serialisable array describing nodes and directed edges for the map.
     *
     * @return array{nodes: array<int,array{id:int,label:string,country:string,biome:string}>, edges: array<int,array{from:int,to:int}>}
     */
    #[Computed]
    public function mapData(): array
    {
        $nodes = [];
        $edges = [];

        foreach ($this->cities as $city) {
            $nodes[] = [
                'id' => $city->id,
                'label' => $city->city,
                'country' => $city->country?->country ?? '',
                'biome' => $city->biome,
            ];

            foreach ($city->neighbors as $neighbor) {
                $edges[] = [
                    'from' => $city->id,
                    'to' => $neighbor->id,
                ];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }
}
