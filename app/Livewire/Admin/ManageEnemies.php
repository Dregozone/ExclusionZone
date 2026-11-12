<?php

namespace App\Livewire\Admin;

use App\Models\City;
use App\Models\Enemy;
use App\Models\Item;
use App\Models\Skill;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Enemies')]
class ManageEnemies extends Component
{
    public bool $showForm = false;

    public bool $showTrashed = false;

    public ?int $editingId = null;

    // Form fields
    public ?int $city_id = null;

    public string $key = '';

    public string $name = '';

    public string $description = '';

    public string $skill_key = 'combat_melee';

    public int $health = 20;

    public int $min_damage = 2;

    public int $max_damage = 5;

    public int $xp_reward = 10;

    /**
     * @var array<int, array{item_key: string, chance_percent: int, quantity: int}>
     */
    public array $loot = [];

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function enemies(): Collection
    {
        $query = Enemy::query()
            ->with('city:id,city')
            ->orderBy('name');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        return $query->get(['id', 'city_id', 'key', 'name', 'skill_key', 'health', 'min_damage', 'max_damage', 'xp_reward', 'deleted_at']);
    }

    #[Computed]
    public function cities(): Collection
    {
        return City::query()->orderBy('city')->get(['id', 'city']);
    }

    #[Computed]
    public function combatSkills(): Collection
    {
        return Skill::query()->whereIn('key', Enemy::COMBAT_SKILLS)->orderBy('display_name')->get(['id', 'key', 'display_name']);
    }

    #[Computed]
    public function items(): Collection
    {
        return Item::query()->orderBy('name')->get(['id', 'key', 'name']);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $enemy = Enemy::query()->findOrFail($id);

        $this->editingId = $id;
        $this->city_id = $enemy->city_id;
        $this->key = $enemy->key;
        $this->name = $enemy->name;
        $this->description = $enemy->description;
        $this->skill_key = $enemy->skill_key;
        $this->health = $enemy->health;
        $this->min_damage = $enemy->min_damage;
        $this->max_damage = $enemy->max_damage;
        $this->xp_reward = $enemy->xp_reward;
        $this->loot = $enemy->lootTable();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'key' => ['required', 'string', 'max:100', Rule::unique('enemies', 'key')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'skill_key' => ['required', Rule::in(Enemy::COMBAT_SKILLS)],
            'health' => ['required', 'integer', 'min:1', 'max:1000'],
            'min_damage' => ['required', 'integer', 'min:0', 'max:100'],
            'max_damage' => ['required', 'integer', 'gte:min_damage', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0', 'max:10000'],
            'loot' => ['array'],
            'loot.*.item_key' => ['required', 'string', 'distinct', 'exists:items,key'],
            'loot.*.chance_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'loot.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $data = [
            ...collect($validated)->except('loot')->all(),
            'loot' => array_map(fn (array $entry): array => [
                'item_key' => $entry['item_key'],
                'chance_percent' => (int) $entry['chance_percent'],
                'quantity' => (int) $entry['quantity'],
            ], array_values($validated['loot'] ?? [])),
        ];

        if ($this->editingId !== null) {
            Enemy::query()->findOrFail($this->editingId)->update($data);
            Flux::toast(variant: 'success', text: __('Enemy updated.'));
        } else {
            Enemy::query()->create($data);
            Flux::toast(variant: 'success', text: __('Enemy created.'));
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->enemies);
    }

    public function addLoot(): void
    {
        $this->loot[] = ['item_key' => '', 'chance_percent' => 50, 'quantity' => 1];
    }

    public function removeLoot(int $index): void
    {
        array_splice($this->loot, $index, 1);
    }

    public function delete(int $id): void
    {
        Enemy::query()->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Enemy deleted.'));
        unset($this->enemies);
    }

    public function restore(int $id): void
    {
        Enemy::withTrashed()->findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Enemy restored.'));
        unset($this->enemies);
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
        $this->key = '';
        $this->name = '';
        $this->description = '';
        $this->skill_key = 'combat_melee';
        $this->health = 20;
        $this->min_damage = 2;
        $this->max_damage = 5;
        $this->xp_reward = 10;
        $this->loot = [];
        $this->resetValidation();
    }
}
