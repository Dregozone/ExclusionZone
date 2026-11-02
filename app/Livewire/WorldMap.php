<?php

namespace App\Livewire;

use App\Actions\Game\BuildWorldMapData;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('World Map')]
class WorldMap extends Component
{
    /**
     * Cities, routes and the signed-in player's position for the map.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function mapData(): array
    {
        /** @var User|null $user */
        $user = auth()->user();

        return (new BuildWorldMapData)($user);
    }
}
