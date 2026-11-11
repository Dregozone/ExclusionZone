<?php

namespace App\Livewire\Admin;

use App\Models\Item;
use App\Models\TroubleType;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Trouble')]
class ManageTroubleTypes extends Component
{
    /**
     * The skills a fight against trouble can test.
     */
    public const COMBAT_SKILL_KEYS = ['combat_melee', 'combat_ranged'];

    public bool $showForm = false;

    public bool $showTrashed = false;

    public ?int $editingId = null;

    // Form fields
    public string $key = '';

    public string $name = '';

    public string $description = '';

    public string $skill_key = 'combat_melee';

    public int $fighter_level = 1;

    public int $damage_min = 5;

    public int $damage_max = 15;

    public int $xp_reward = 30;

    public ?int $loot_item_id = null;

    public int $loot_quantity = 0;

    public int $demand_value = 3;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function troubleTypes(): Collection
    {
        $query = TroubleType::query()->with('lootItem:id,name')->orderBy('fighter_level')->orderBy('name');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        return $query->get();
    }

    #[Computed]
    public function items(): Collection
    {
        return Item::query()->orderBy('name')->get(['id', 'name']);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $troubleType = TroubleType::query()->findOrFail($id);

        $this->editingId = $id;
        $this->key = $troubleType->key;
        $this->name = $troubleType->name;
        $this->description = $troubleType->description;
        $this->skill_key = $troubleType->skill_key;
        $this->fighter_level = $troubleType->fighter_level;
        $this->damage_min = $troubleType->damage_min;
        $this->damage_max = $troubleType->damage_max;
        $this->xp_reward = $troubleType->xp_reward;
        $this->loot_item_id = $troubleType->loot_item_id;
        $this->loot_quantity = $troubleType->loot_quantity;
        $this->demand_value = $troubleType->demand_value;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'key' => ['required', 'string', 'max:100', Rule::unique('trouble_types', 'key')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'skill_key' => ['required', Rule::in(self::COMBAT_SKILL_KEYS)],
            'fighter_level' => ['required', 'integer', 'min:1', 'max:100'],
            'damage_min' => ['required', 'integer', 'min:0', 'max:100'],
            'damage_max' => ['required', 'integer', 'gte:damage_min', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0', 'max:10000'],
            'loot_item_id' => ['nullable', 'integer', 'exists:items,id'],
            'loot_quantity' => ['required', 'integer', 'min:0', 'max:1000'],
            'demand_value' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        if ($this->editingId !== null) {
            TroubleType::query()->findOrFail($this->editingId)->update($validated);
            Flux::toast(variant: 'success', text: __('Trouble updated.'));
        } else {
            TroubleType::query()->create($validated);
            Flux::toast(variant: 'success', text: __('Trouble created.'));
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->troubleTypes);
    }

    public function delete(int $id): void
    {
        TroubleType::query()->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Trouble deleted.'));
        unset($this->troubleTypes);
    }

    public function restore(int $id): void
    {
        TroubleType::withTrashed()->findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Trouble restored.'));
        unset($this->troubleTypes);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->key = '';
        $this->name = '';
        $this->description = '';
        $this->skill_key = 'combat_melee';
        $this->fighter_level = 1;
        $this->damage_min = 5;
        $this->damage_max = 15;
        $this->xp_reward = 30;
        $this->loot_item_id = null;
        $this->loot_quantity = 0;
        $this->demand_value = 3;
        $this->resetValidation();
    }
}
