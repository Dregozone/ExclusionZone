<?php

namespace App\Livewire;

use App\Models\RadioMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Radio Log')]
class RadioHistory extends Component
{
    public function boot(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user?->canPerformTask('chat_send'), 403);
    }

    /**
     * Every transmission sent since midnight, oldest first.
     *
     * @return Collection<int, RadioMessage>
     */
    #[Computed]
    public function messages(): Collection
    {
        return RadioMessage::query()
            ->with('user:id,name')
            ->where('created_at', '>=', today())
            ->oldest('id')
            ->get();
    }

    #[On('echo-private:radio,RadioMessageSent')]
    public function refreshMessages(): void
    {
        unset($this->messages);
    }
}
