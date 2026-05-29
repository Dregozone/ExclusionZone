<?php

namespace App\Livewire\Admin;

use App\Models\City;
use App\Models\User;
use App\Models\UserLocation;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Move Player')]
class MovePlayer extends Component
{
    public ?int $selectedUserId = null;

    public ?int $selectedCityId = null;

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
    public function cities(): Collection
    {
        return City::query()
            ->with('country:id,country')
            ->orderBy('city')
            ->get(['id', 'country_id', 'city']);
    }

    public function movePlayer(): void
    {
        $this->validate([
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
            'selectedCityId' => ['required', 'integer', 'exists:cities,id'],
        ]);

        /** @var User $user */
        $user = User::query()->select(['id', 'name'])->find($this->selectedUserId);

        /** @var City $city */
        $city = City::query()->select(['id', 'country_id', 'city'])->find($this->selectedCityId);

        UserLocation::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'country_id' => $city->country_id,
                'city_id' => $city->id,
            ],
        );

        Flux::toast(variant: 'success', text: "{$user->name} has been moved to {$city->city}.");

        $this->selectedUserId = null;
        $this->selectedCityId = null;
    }
}
