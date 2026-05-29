<?php

namespace App\Livewire\Admin;

use App\Models\City;
use App\Models\CityAction;
use App\Models\Skill;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage City Actions')]
class ManageCityActions extends Component
{
    public bool $showForm = false;

    public bool $showTrashed = false;

    public ?int $editingId = null;

    // Form fields
    public ?int $city_id = null;

    public string $action_key = '';

    public string $label = '';

    public string $description = '';

    public string $skill_key = '';

    public int $min_level = 1;

    public string $risk_level = 'medium';

    public int $reward_xp = 20;

    public string $reward_item_key = '';

    public int $reward_quantity = 1;

    public string $reward_loot_tier = 'medium';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function actions(): Collection
    {
        $query = CityAction::query()
            ->with('city:id,city')
            ->orderBy('label');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        return $query->get(['id', 'city_id', 'action_key', 'label', 'description', 'skill_key', 'min_level', 'risk_level', 'reward_profile', 'deleted_at']);
    }

    #[Computed]
    public function cities(): Collection
    {
        return City::query()->orderBy('city')->get(['id', 'city']);
    }

    #[Computed]
    public function skills(): Collection
    {
        return Skill::query()->orderBy('display_name')->get(['id', 'key', 'display_name']);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $action = CityAction::query()->findOrFail($id);
        $reward = $action->reward_profile;

        $this->editingId = $id;
        $this->city_id = $action->city_id;
        $this->action_key = $action->action_key;
        $this->label = $action->label;
        $this->description = $action->description;
        $this->skill_key = $action->skill_key;
        $this->min_level = $action->min_level;
        $this->risk_level = $action->risk_level;
        $this->reward_xp = $reward['xp'] ?? 20;
        $this->reward_item_key = $reward['item_key'] ?? '';
        $this->reward_quantity = $reward['quantity'] ?? 1;
        $this->reward_loot_tier = $reward['loot_tier'] ?? 'medium';
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'action_key' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'skill_key' => ['required', 'string', 'max:100'],
            'min_level' => ['required', 'integer', 'min:1', 'max:100'],
            'risk_level' => ['required', 'string', 'in:low,medium,high,extreme'],
            'reward_xp' => ['required', 'integer', 'min:1'],
            'reward_item_key' => ['required', 'string', 'max:100'],
            'reward_quantity' => ['required', 'integer', 'min:1'],
            'reward_loot_tier' => ['required', 'string', 'max:50'],
        ]);

        $data = [
            'city_id' => $validated['city_id'],
            'action_key' => $validated['action_key'],
            'label' => $validated['label'],
            'description' => $validated['description'],
            'skill_key' => $validated['skill_key'],
            'min_level' => $validated['min_level'],
            'risk_level' => $validated['risk_level'],
            'reward_profile' => [
                'xp' => $validated['reward_xp'],
                'item_key' => $validated['reward_item_key'],
                'quantity' => $validated['reward_quantity'],
                'loot_tier' => $validated['reward_loot_tier'],
            ],
        ];

        if ($this->editingId !== null) {
            CityAction::query()->findOrFail($this->editingId)->update($data);
            Flux::toast(variant: 'success', text: __('Action updated.'));
        } else {
            CityAction::query()->create($data);
            Flux::toast(variant: 'success', text: __('Action created.'));
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->actions);
    }

    public function delete(int $id): void
    {
        CityAction::query()->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Action deleted.'));
        unset($this->actions);
    }

    public function restore(int $id): void
    {
        CityAction::withTrashed()->findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Action restored.'));
        unset($this->actions);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->city_id = null;
        $this->action_key = '';
        $this->label = '';
        $this->description = '';
        $this->skill_key = '';
        $this->min_level = 1;
        $this->risk_level = 'medium';
        $this->reward_xp = 20;
        $this->reward_item_key = '';
        $this->reward_quantity = 1;
        $this->reward_loot_tier = 'medium';
    }
}
