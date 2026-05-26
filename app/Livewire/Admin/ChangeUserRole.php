<?php

namespace App\Livewire\Admin;

use App\Actions\Game\ChangeUserRole as ChangeUserRoleAction;
use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Change User Role')]
class ChangeUserRole extends Component
{
    public ?int $selectedUserId = null;

    public ?int $selectedRoleId = null;

    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->with('role:id,key,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role_id']);
    }

    #[Computed]
    public function roles(): Collection
    {
        return Role::query()
            ->with('tasks')
            ->orderBy('name')
            ->get(['id', 'key', 'name']);
    }

    #[Computed]
    public function selectedUser(): ?User
    {
        if ($this->selectedUserId === null) {
            return null;
        }

        return User::query()
            ->with('role.tasks')
            ->find($this->selectedUserId);
    }

    #[Computed]
    public function selectedRole(): ?Role
    {
        if ($this->selectedRoleId === null) {
            return null;
        }

        return Role::query()
            ->with('tasks')
            ->find($this->selectedRoleId);
    }

    public function updatedSelectedUserId(): void
    {
        $this->selectedRoleId = $this->selectedUser()?->role_id;
    }

    public function changeRole(ChangeUserRoleAction $changeUserRole): void
    {
        $validated = $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'selectedRoleId' => ['required', 'integer', 'exists:roles,id'],
        ]);

        /** @var User $target */
        $target = User::query()
            ->with('role')
            ->findOrFail($validated['selectedUserId']);

        if ((int) $target->role_id === (int) $validated['selectedRoleId']) {
            Flux::toast(variant: 'warning', text: __(':name already has that role.', ['name' => $target->name]));

            return;
        }

        /** @var Role $role */
        $role = Role::query()->findOrFail($validated['selectedRoleId']);

        /** @var User $actor */
        $actor = auth()->user();

        $changeUserRole($actor, $target, $role, authorize: false);

        $this->selectedRoleId = $role->id;

        Flux::toast(variant: 'success', text: __(':name is now assigned to the :role role.', [
            'name' => $target->name,
            'role' => $role->name,
        ]));
    }
}
