<?php

namespace App\Livewire\Admin;

use App\Models\City;
use App\Models\Country;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Locations')]
class ManageLocations extends Component
{
    public bool $showForm = false;

    public bool $showTrashed = false;

    public ?int $editingId = null;

    // Form fields
    public string $city = '';

    public ?int $country_id = null;

    public string $biome = '';

    public int $rain_chance_pct = 0;

    public int $trouble_chance_pct = 0;

    public string $baseline_loot_tier = 'medium';

    /** @var array<int> */
    public array $neighborIds = [];

    #[Computed]
    public function cities(): Collection
    {
        $query = City::query()
            ->with('country:id,country')
            ->orderBy('city');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        return $query->get(['id', 'country_id', 'city', 'biome', 'rain_chance_pct', 'trouble_chance_pct', 'baseline_loot_tier', 'deleted_at']);
    }

    #[Computed]
    public function countries(): Collection
    {
        return Country::query()
            ->orderBy('country')
            ->get(['id', 'country']);
    }

    #[Computed]
    public function allCities(): Collection
    {
        return City::query()
            ->orderBy('city')
            ->get(['id', 'city']);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $city = City::query()
            ->with('neighbors:id')
            ->findOrFail($id);

        $this->editingId = $id;
        $this->city = $city->city;
        $this->country_id = $city->country_id;
        $this->biome = $city->biome;
        $this->rain_chance_pct = $city->rain_chance_pct;
        $this->trouble_chance_pct = $city->trouble_chance_pct;
        $this->baseline_loot_tier = $city->baseline_loot_tier;
        $this->neighborIds = $city->neighbors->pluck('id')->map(fn ($v) => (int) $v)->toArray();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'city' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'biome' => ['required', 'string', 'max:255'],
            'rain_chance_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'trouble_chance_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'baseline_loot_tier' => ['required', 'string', 'max:50'],
            'neighborIds' => ['array'],
            'neighborIds.*' => ['integer', 'exists:cities,id'],
        ]);

        if ($this->editingId !== null) {
            /** @var City $location */
            $location = City::query()->findOrFail($this->editingId);
            $location->update([
                'city' => $validated['city'],
                'country_id' => $validated['country_id'],
                'biome' => $validated['biome'],
                'rain_chance_pct' => $validated['rain_chance_pct'],
                'trouble_chance_pct' => $validated['trouble_chance_pct'],
                'baseline_loot_tier' => $validated['baseline_loot_tier'],
            ]);
        } else {
            $location = City::query()->create([
                'city' => $validated['city'],
                'country_id' => $validated['country_id'],
                'biome' => $validated['biome'],
                'rain_chance_pct' => $validated['rain_chance_pct'],
                'trouble_chance_pct' => $validated['trouble_chance_pct'],
                'baseline_loot_tier' => $validated['baseline_loot_tier'],
            ]);
        }

        $location->neighbors()->sync($validated['neighborIds']);

        Flux::toast(variant: 'success', text: $this->editingId ? __('Location updated.') : __('Location created.'));

        $this->showForm = false;
        $this->resetForm();
        unset($this->cities);
    }

    public function delete(int $id): void
    {
        City::query()->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Location deleted.'));
        unset($this->cities);
    }

    public function restore(int $id): void
    {
        City::withTrashed()->findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Location restored.'));
        unset($this->cities);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->city = '';
        $this->country_id = null;
        $this->biome = '';
        $this->rain_chance_pct = 0;
        $this->trouble_chance_pct = 0;
        $this->baseline_loot_tier = 'medium';
        $this->neighborIds = [];
        $this->editingId = null;
    }
}
