<?php

namespace App\Livewire\Admin;

use App\Models\Item;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
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

        return $query->get(['id', 'key', 'name', 'description', 'deleted_at']);
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
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'key' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
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
    }
}
