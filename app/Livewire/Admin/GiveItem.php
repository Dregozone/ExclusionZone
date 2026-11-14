<?php

namespace App\Livewire\Admin;

use App\Models\Item;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Give Item')]
class GiveItem extends Component
{
    public ?int $selectedUserId = null;

    public ?int $selectedItemId = null;

    public ?int $quantity = 1;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    #[Computed]
    public function items(): Collection
    {
        return Item::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function giveItem(): void
    {
        $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'selectedItemId' => ['required', 'integer', Rule::exists('items', 'id')->withoutTrashed()],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        /** @var User $user */
        $user = User::query()->select(['id', 'name'])->find($this->selectedUserId);

        /** @var Item $item */
        $item = Item::query()->select(['id', 'name'])->find($this->selectedItemId);

        DB::transaction(function () use ($user, $item): void {
            $user->inventoryItems()
                ->firstOrCreate(['item_id' => $item->id], ['quantity' => 0])
                ->increment('quantity', $this->quantity);
        });

        Flux::toast(variant: 'success', text: "{$user->name} has been given {$this->quantity} × {$item->name}.");

        $this->reset(['selectedUserId', 'selectedItemId', 'quantity']);
    }
}
