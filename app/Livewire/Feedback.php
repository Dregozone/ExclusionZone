<?php

namespace App\Livewire;

use App\FeedbackType;
use App\Models\FeedbackReport;
use App\Models\Idea;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Where survivors suggest and upvote ideas, and privately report bugs or send feedback to the admins.
 */
#[Title('Feedback')]
class Feedback extends Component
{
    /**
     * How many ideas and reports one survivor can submit per hour.
     */
    public const SUBMISSIONS_PER_HOUR = 10;

    #[Url]
    public string $tab = 'ideas';

    public string $ideaTitle = '';

    public string $ideaDescription = '';

    public string $reportType = 'bug';

    public string $reportTitle = '';

    public string $reportDetails = '';

    /**
     * Ideas with the most votes first, each flagged with whether the viewer has voted for it.
     *
     * @return Collection<int, Idea>
     */
    #[Computed]
    public function ideas(): Collection
    {
        return Idea::query()
            ->with('user:id,name')
            ->withCount('voters')
            ->withExists(['voters as has_voted' => fn ($query) => $query->whereKey(auth()->id())])
            ->orderByDesc('voters_count')
            ->latest('id')
            ->get();
    }

    /**
     * The viewer's own bug reports and feedback, newest first.
     *
     * @return Collection<int, FeedbackReport>
     */
    #[Computed]
    public function myReports(): Collection
    {
        return FeedbackReport::query()
            ->whereBelongsTo($this->user())
            ->latest('id')
            ->get();
    }

    public function submitIdea(): void
    {
        $validated = $this->validate([
            'ideaTitle' => ['required', 'string', 'max:'.Idea::TITLE_MAX_LENGTH],
            'ideaDescription' => ['nullable', 'string', 'max:'.Idea::DESCRIPTION_MAX_LENGTH],
        ]);

        $this->ensureNotSubmittingTooOften('ideaTitle');

        $idea = $this->user()->ideas()->create([
            'title' => $validated['ideaTitle'],
            'description' => $validated['ideaDescription'] ?: null,
        ]);
        $idea->voters()->attach($this->user());

        $this->reset('ideaTitle', 'ideaDescription');
        unset($this->ideas);

        Flux::toast(variant: 'success', text: __('Idea posted. Your vote has been added.'));
    }

    /**
     * Add the viewer's vote to an idea, or take it back.
     */
    public function toggleVote(int $ideaId): void
    {
        Idea::query()->findOrFail($ideaId)->voters()->toggle([$this->user()->id]);

        unset($this->ideas);
    }

    public function deleteIdea(int $ideaId): void
    {
        abort_unless($this->user()->isAdmin(), 403);

        Idea::query()->findOrFail($ideaId)->delete();

        unset($this->ideas);

        Flux::toast(variant: 'success', text: __('Idea removed.'));
    }

    public function submitReport(): void
    {
        $validated = $this->validate([
            'reportType' => ['required', Rule::enum(FeedbackType::class)],
            'reportTitle' => ['required', 'string', 'max:'.FeedbackReport::TITLE_MAX_LENGTH],
            'reportDetails' => ['required', 'string', 'max:'.FeedbackReport::DETAILS_MAX_LENGTH],
        ]);

        $this->ensureNotSubmittingTooOften('reportTitle');

        $this->user()->feedbackReports()->create([
            'type' => $validated['reportType'],
            'title' => $validated['reportTitle'],
            'details' => $validated['reportDetails'],
        ]);

        $this->reset('reportTitle', 'reportDetails');
        unset($this->myReports);

        Flux::toast(variant: 'success', text: __('Thanks — your report has been sent to the admins.'));
    }

    private function ensureNotSubmittingTooOften(string $field): void
    {
        $key = 'feedback-submissions:'.$this->user()->id;

        if (RateLimiter::tooManyAttempts($key, self::SUBMISSIONS_PER_HOUR)) {
            throw ValidationException::withMessages([
                $field => __('You have sent a lot recently. Try again in :minutes minutes.', [
                    'minutes' => (int) ceil(RateLimiter::availableIn($key) / 60),
                ]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }
}
