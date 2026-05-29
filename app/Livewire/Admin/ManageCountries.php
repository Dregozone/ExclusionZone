<?php

namespace App\Livewire\Admin;

use App\Models\Country;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Countries')]
class ManageCountries extends Component
{
    public bool $showForm = false;

    public bool $showTrashed = false;

    public ?int $editingId = null;

    // Form fields
    public string $continent = '';

    public string $country = '';

    public int $avg_temp_c = 10;

    public int $rain_chance_pct = 40;

    public int $trouble_chance_pct = 40;

    public string $notes = '';

    #[Computed]
    public function countries(): Collection
    {
        $query = Country::query()->orderBy('country');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        return $query->get(['id', 'continent', 'country', 'avg_temp_c', 'rain_chance_pct', 'trouble_chance_pct', 'notes', 'deleted_at']);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $country = Country::query()->findOrFail($id);

        $this->editingId = $id;
        $this->continent = $country->continent;
        $this->country = $country->country;
        $this->avg_temp_c = $country->avg_temp_c;
        $this->rain_chance_pct = $country->rain_chance_pct;
        $this->trouble_chance_pct = $country->trouble_chance_pct;
        $this->notes = $country->notes ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'continent' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:255'],
            'avg_temp_c' => ['required', 'integer', 'min:-60', 'max:60'],
            'rain_chance_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'trouble_chance_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->editingId !== null) {
            Country::query()->findOrFail($this->editingId)->update($validated);
            Flux::toast(variant: 'success', text: __('Country updated.'));
        } else {
            Country::query()->create($validated);
            Flux::toast(variant: 'success', text: __('Country created.'));
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->countries);
    }

    public function delete(int $id): void
    {
        Country::query()->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Country deleted.'));
        unset($this->countries);
    }

    public function restore(int $id): void
    {
        Country::withTrashed()->findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Country restored.'));
        unset($this->countries);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->continent = '';
        $this->country = '';
        $this->avg_temp_c = 10;
        $this->rain_chance_pct = 40;
        $this->trouble_chance_pct = 40;
        $this->notes = '';
    }
}
