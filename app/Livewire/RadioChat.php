<?php

namespace App\Livewire;

use App\Actions\Game\SendRadioMessage;
use App\Models\RadioMessage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RadioChat extends Component
{
    public const RECENT_MESSAGE_LIMIT = 20;

    public string $message = '';

    /**
     * Only listen on the radio channel when the player is allowed to join it.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return $this->canUseRadio
            ? ['echo-private:radio,RadioMessageSent' => 'refreshMessages']
            : [];
    }

    #[Computed]
    public function canUseRadio(): bool
    {
        return $this->player()->canPerformTask('chat_send');
    }

    /**
     * The most recent transmissions, oldest first so the newest sits at the bottom.
     *
     * @return Collection<int, RadioMessage>
     */
    #[Computed]
    public function messages(): Collection
    {
        if (! $this->canUseRadio) {
            return collect();
        }

        return RadioMessage::query()
            ->with('user:id,name')
            ->latest('id')
            ->limit(self::RECENT_MESSAGE_LIMIT)
            ->get()
            ->reverse()
            ->values();
    }

    public function send(SendRadioMessage $sendRadioMessage): void
    {
        $this->validate([
            'message' => ['required', 'string', 'max:'.RadioMessage::MAX_LENGTH],
        ]);

        try {
            $sendRadioMessage($this->player(), $this->message);
        } catch (AuthorizationException $exception) {
            $this->addError('message', $exception->getMessage());

            return;
        }

        $this->reset('message');
        $this->refreshMessages();
    }

    public function refreshMessages(): void
    {
        unset($this->messages);
    }

    private function player(): User
    {
        /** @var User */
        return auth()->user();
    }
}
