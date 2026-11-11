<?php

namespace App\Livewire\Admin;

use App\ItemRarity;
use App\Models\Item;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Items')]
class ManageItems extends Component
{
    public bool $showForm = false;

    public bool $showTrashed = false;

    public ?int $editingId = null;

    // Form fields
    public string $key = '';

    public string $name = '';

    public string $description = '';

    public string $rarity = 'common';

    public bool $is_quest_item = false;

    public int $hunger_restored = 0;

    public int $fatigue_restored = 0;

    public int $health_restored = 0;

    public int $attack_bonus = 0;

    public bool $is_medical = false;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function items(): Collection
    {
        $query = Item::query()->orderBy('name');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        return $query->get(['id', 'key', 'name', 'description', 'rarity', 'is_quest_item', 'hunger_restored', 'fatigue_restored', 'health_restored', 'attack_bonus', 'is_medical', 'deleted_at']);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $item = Item::query()->findOrFail($id);

        $this->editingId = $id;
        $this->key = $item->key;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->rarity = $item->rarity->value;
        $this->is_quest_item = $item->is_quest_item;
        $this->hunger_restored = $item->hunger_restored;
        $this->fatigue_restored = $item->fatigue_restored;
        $this->health_restored = $item->health_restored;
        $this->attack_bonus = $item->attack_bonus;
        $this->is_medical = $item->is_medical;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'key' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'rarity' => ['required', Rule::enum(ItemRarity::class)],
            'is_quest_item' => ['boolean'],
            'hunger_restored' => ['required', 'integer', 'min:0', 'max:100'],
            'fatigue_restored' => ['required', 'integer', 'min:0', 'max:100'],
            'health_restored' => ['required', 'integer', 'min:0', 'max:100'],
            'attack_bonus' => ['required', 'integer', 'min:0', 'max:100'],
            'is_medical' => ['boolean'],
        ]);

        if ($this->editingId !== null) {
            Item::query()->findOrFail($this->editingId)->update($validated);
            Flux::toast(variant: 'success', text: __('Item updated.'));
        } else {
            Item::query()->create($validated);
            Flux::toast(variant: 'success', text: __('Item created.'));
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->items);
    }

    public function delete(int $id): void
    {
        Item::query()->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Item deleted.'));
        unset($this->items);
    }

    public function restore(int $id): void
    {
        Item::withTrashed()->findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Item restored.'));
        unset($this->items);
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
        $this->rarity = 'common';
        $this->is_quest_item = false;
        $this->hunger_restored = 0;
        $this->fatigue_restored = 0;
        $this->health_restored = 0;
        $this->attack_bonus = 0;
        $this->is_medical = false;
    }
}
