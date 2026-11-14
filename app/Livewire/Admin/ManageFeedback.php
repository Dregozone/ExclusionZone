<?php

namespace App\Livewire\Admin;

use App\FeedbackType;
use App\Models\FeedbackReport;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin inbox for the private bug reports and feedback sent from the Feedback page.
 */
#[Title('Feedback Reports')]
class ManageFeedback extends Component
{
    /**
     * A FeedbackType value, or empty for every type.
     */
    #[Url]
    public string $type = '';

    #[Url]
    public bool $showResolved = false;

    public ?int $expandedId = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    /**
     * @return Collection<int, FeedbackReport>
     */
    #[Computed]
    public function reports(): Collection
    {
        return FeedbackReport::query()
            ->with('user:id,name,email')
            ->when(FeedbackType::tryFrom($this->type), fn ($query, FeedbackType $type) => $query->where('type', $type))
            ->unless($this->showResolved, fn ($query) => $query->open())
            ->latest('id')
            ->get();
    }

    #[Computed]
    public function openCount(): int
    {
        return FeedbackReport::query()->open()->count();
    }

    public function toggleExpanded(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function resolve(int $id): void
    {
        FeedbackReport::query()->findOrFail($id)->update(['resolved_at' => now()]);
        Flux::toast(variant: 'success', text: __('Report marked as resolved.'));
        unset($this->reports, $this->openCount);
    }

    public function reopen(int $id): void
    {
        FeedbackReport::query()->findOrFail($id)->update(['resolved_at' => null]);
        Flux::toast(variant: 'success', text: __('Report reopened.'));
        unset($this->reports, $this->openCount);
    }
}
