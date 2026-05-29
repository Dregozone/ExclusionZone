<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Manage Skills')]
class ManageSkills extends Component
{
    public bool $showForm = false;

    public bool $showTrashed = false;

    public ?int $editingId = null;

    // Form fields
    public string $key = '';

    public string $display_name = '';

    public string $description = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function skills(): Collection
    {
        $query = Skill::query()->orderBy('display_name');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        return $query->get(['id', 'key', 'display_name', 'description', 'deleted_at']);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $skill = Skill::query()->findOrFail($id);

        $this->editingId = $id;
        $this->key = $skill->key;
        $this->display_name = $skill->display_name;
        $this->description = $skill->description;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'key' => ['required', 'string', 'max:100'],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        if ($this->editingId !== null) {
            Skill::query()->findOrFail($this->editingId)->update($validated);
            Flux::toast(variant: 'success', text: __('Skill updated.'));
        } else {
            Skill::query()->create($validated);
            Flux::toast(variant: 'success', text: __('Skill created.'));
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->skills);
    }

    public function delete(int $id): void
    {
        Skill::query()->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Skill deleted.'));
        unset($this->skills);
    }

    public function restore(int $id): void
    {
        Skill::withTrashed()->findOrFail($id)->restore();
        Flux::toast(variant: 'success', text: __('Skill restored.'));
        unset($this->skills);
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
        $this->display_name = '';
        $this->description = '';
    }
}
