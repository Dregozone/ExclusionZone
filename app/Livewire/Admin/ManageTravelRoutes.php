<?php

namespace App\Livewire\Admin;

use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Travel Routes')]
class ManageTravelRoutes extends Component
{
    public ?int $editingCityId = null;

    public ?int $editingNeighborId = null;

    public int $editingDuration = 30;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    /** @return Collection<int, object> */
    #[Computed]
    public function routes(): Collection
    {
        return DB::table('city_connections')
            ->join('cities as from_city', 'city_connections.city_id', '=', 'from_city.id')
            ->join('cities as to_city', 'city_connections.neighbor_city_id', '=', 'to_city.id')
            ->orderBy('from_city.city')
            ->orderBy('to_city.city')
            ->select(
                'city_connections.city_id',
                'city_connections.neighbor_city_id',
                'city_connections.duration_seconds',
                'from_city.city as from_city_name',
                'to_city.city as to_city_name',
            )
            ->get();
    }

    public function edit(int $cityId, int $neighborId, int $currentDuration): void
    {
        $this->editingCityId = $cityId;
        $this->editingNeighborId = $neighborId;
        $this->editingDuration = $currentDuration;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'editingDuration' => ['required', 'integer', 'min:10'],
        ]);

        DB::table('city_connections')
            ->where('city_id', $this->editingCityId)
            ->where('neighbor_city_id', $this->editingNeighborId)
            ->update(['duration_seconds' => $validated['editingDuration']]);

        Flux::toast(variant: 'success', text: __('Travel duration updated.'));

        $this->editingCityId = null;
        $this->editingNeighborId = null;
        unset($this->routes);
    }

    public function cancel(): void
    {
        $this->editingCityId = null;
        $this->editingNeighborId = null;
    }
}
