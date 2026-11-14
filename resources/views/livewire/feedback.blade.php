<section class="mx-auto w-full max-w-4xl">
    <div class="mb-6">
        <p class="ez-label">{{ __('Field station') }}</p>
        <flux:heading size="xl" class="ez-display !text-4xl !font-normal sm:!text-5xl">{{ __('Feedback') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Help steer the Zone: vote for the ideas you want, or tell the admins privately about a bug.') }}</flux:text>
    </div>

    <div class="mb-6 flex gap-2 border-b border-ez-line" role="tablist">
        @foreach (['ideas' => __('Ideas board'), 'report' => __('Report a bug or send feedback')] as $tabKey => $tabLabel)
            <button
                type="button"
                role="tab"
                wire:click="$set('tab', '{{ $tabKey }}')"
                aria-selected="{{ $tab === $tabKey ? 'true' : 'false' }}"
                @class([
                    '-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                    'border-ez-signal text-ez-ink' => $tab === $tabKey,
                    'border-transparent text-ez-muted hover:text-ez-ink' => $tab !== $tabKey,
                ])
            >{{ $tabLabel }}</button>
        @endforeach
    </div>

    @if ($tab === 'report')
        <div class="grid gap-6 lg:grid-cols-5">
            <div class="ez-panel p-6 lg:col-span-3">
                <flux:heading size="lg">{{ __('Send a private report') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Only you and the admins can see what you send here.') }}</flux:text>

                <form wire:submit="submitReport" class="mt-5 space-y-5">
                    <flux:field>
                        <flux:label>{{ __('Type') }}</flux:label>
                        <flux:select wire:model.live="reportType">
                            @foreach (\App\FeedbackType::cases() as $feedbackType)
                                <flux:select.option value="{{ $feedbackType->value }}">{{ $feedbackType->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="reportType" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Title') }}</flux:label>
                        <flux:input wire:model="reportTitle" maxlength="{{ \App\Models\FeedbackReport::TITLE_MAX_LENGTH }}" placeholder="{{ $reportType === 'bug' ? __('e.g. Travel timer stuck at 0:00') : __('e.g. Fishing feels too slow') }}" />
                        <flux:error name="reportTitle" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ $reportType === 'bug' ? __('Steps to reproduce') : __('Details') }}</flux:label>
                        <flux:textarea
                            wire:model="reportDetails"
                            rows="6"
                            maxlength="{{ \App\Models\FeedbackReport::DETAILS_MAX_LENGTH }}"
                            :placeholder="$reportType === 'bug'
                                ? implode(PHP_EOL, [__('1. Where you were and what you did'), __('2. What you expected to happen'), __('3. What happened instead')])
                                : __('Tell us what you think…')"
                        />
                        <flux:error name="reportDetails" />
                    </flux:field>

                    <flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Send report') }}</flux:button>
                </form>
            </div>

            <div class="lg:col-span-2">
                <p class="ez-label mb-3">{{ __('Your reports') }}</p>
                <div class="space-y-2">
                    @forelse ($this->myReports as $report)
                        <div wire:key="my-report-{{ $report->id }}" class="ez-card p-3">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-ez-ink">{{ $report->title }}</p>
                                <span @class([
                                    'shrink-0 rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-ez-safe/15 text-ez-safe' => $report->isResolved(),
                                    'bg-ez-warn/15 text-ez-warn' => ! $report->isResolved(),
                                ])>{{ $report->isResolved() ? __('Resolved') : __('Open') }}</span>
                            </div>
                            <p class="mt-1 font-mono text-[11px] text-ez-muted">{{ $report->type->label() }} · {{ $report->created_at->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ez-muted">{{ __('You have not sent any reports yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <div class="ez-panel mb-6 p-6">
            <flux:heading size="lg">{{ __('Suggest an idea') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Ideas are public. Other survivors can upvote the ones they would like to see.') }}</flux:text>

            <form wire:submit="submitIdea" class="mt-5 space-y-5">
                <flux:field>
                    <flux:label>{{ __('Idea') }}</flux:label>
                    <flux:input wire:model="ideaTitle" maxlength="{{ \App\Models\Idea::TITLE_MAX_LENGTH }}" placeholder="{{ __('e.g. Let survivors build a camp') }}" />
                    <flux:error name="ideaTitle" />
                </flux:field>

                <flux:field>
                    <flux:label badge="{{ __('Optional') }}">{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="ideaDescription" rows="3" maxlength="{{ \App\Models\Idea::DESCRIPTION_MAX_LENGTH }}" placeholder="{{ __('Why would it make the Zone better?') }}" />
                    <flux:error name="ideaDescription" />
                </flux:field>

                <flux:button type="submit" variant="primary" icon="light-bulb">{{ __('Post idea') }}</flux:button>
            </form>
        </div>

        <div class="space-y-3">
            @forelse ($this->ideas as $idea)
                <div wire:key="idea-{{ $idea->id }}" class="ez-card flex gap-4 p-4">
                    <button
                        type="button"
                        wire:click="toggleVote({{ $idea->id }})"
                        aria-pressed="{{ $idea->has_voted ? 'true' : 'false' }}"
                        aria-label="{{ $idea->has_voted ? __('Remove your vote') : __('Upvote this idea') }}"
                        @class([
                            'flex w-14 shrink-0 flex-col items-center justify-center rounded-md border py-2 transition-colors',
                            'border-ez-signal bg-ez-signal/15 text-ez-signal' => $idea->has_voted,
                            'border-ez-line text-ez-muted hover:border-ez-signal/60 hover:text-ez-ink' => ! $idea->has_voted,
                        ])
                    >
                        <flux:icon.chevron-up variant="micro" />
                        <span class="font-mono text-lg leading-none">{{ $idea->voters_count }}</span>
                    </button>

                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-ez-ink">{{ $idea->title }}</p>
                        @if ($idea->description)
                            <p class="mt-1 whitespace-pre-line text-sm text-ez-muted">{{ $idea->description }}</p>
                        @endif
                        <p class="mt-2 font-mono text-[11px] text-ez-muted">{{ $idea->user?->name }} · {{ $idea->created_at->diffForHumans() }}</p>
                    </div>

                    @if (auth()->user()->isAdmin())
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteIdea({{ $idea->id }})" wire:confirm="{{ __('Remove this idea and its votes?') }}" aria-label="{{ __('Remove idea') }}" />
                    @endif
                </div>
            @empty
                <p class="py-10 text-center text-sm text-ez-muted">{{ __('No ideas yet. Be the first to suggest one.') }}</p>
            @endforelse
        </div>
    @endif
</section>
