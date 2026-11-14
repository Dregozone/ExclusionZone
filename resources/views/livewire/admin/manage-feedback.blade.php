<section class="w-full">
    <div class="mb-6">
        <p class="ez-label !text-amber-400">{{ __('Admin console') }}</p>
        <flux:heading size="xl" class="ez-display !text-4xl !font-normal sm:!text-5xl">{{ __('Feedback Reports') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Private bug reports and feedback from survivors. :count open.', ['count' => $this->openCount]) }}</flux:text>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-4">
        <div class="w-48">
            <flux:select wire:model.live="type" aria-label="{{ __('Filter by type') }}">
                <flux:select.option value="">{{ __('All types') }}</flux:select.option>
                @foreach (\App\FeedbackType::cases() as $feedbackType)
                    <flux:select.option value="{{ $feedbackType->value }}">{{ $feedbackType->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="flex items-center gap-3">
            <flux:checkbox wire:model.live="showResolved" id="showResolved" />
            <label for="showResolved" class="cursor-pointer text-sm text-zinc-600 dark:text-zinc-400">{{ __('Show resolved reports') }}</label>
        </div>
    </div>

    <div class="ez-panel overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-md:hidden">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Reported by') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Sent') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->reports as $report)
                    <tr wire:key="report-{{ $report->id }}" class="{{ $report->isResolved() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            <button type="button" wire:click="toggleExpanded({{ $report->id }})" class="text-left hover:underline" aria-expanded="{{ $expandedId === $report->id ? 'true' : 'false' }}">
                                {{ $report->title }}
                            </button>
                            @if ($report->isResolved())
                                <span class="ml-2 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('Resolved') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-md:hidden">
                            <span @class([
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' => $report->type === \App\FeedbackType::Bug,
                                'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400' => $report->type === \App\FeedbackType::Feedback,
                            ])>{{ $report->type->label() }}</span>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-lg:hidden">{{ $report->user?->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-lg:hidden" title="{{ $report->created_at->toDayDateTimeString() }}">{{ $report->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <flux:button size="sm" wire:click="toggleExpanded({{ $report->id }})" variant="ghost" :icon="$expandedId === $report->id ? 'chevron-up' : 'chevron-down'">{{ __('Details') }}</flux:button>
                            @if ($report->isResolved())
                                <flux:button size="sm" wire:click="reopen({{ $report->id }})" variant="ghost" icon="arrow-path">{{ __('Reopen') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="resolve({{ $report->id }})" variant="ghost" icon="check">{{ __('Resolve') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                    @if ($expandedId === $report->id)
                        <tr wire:key="report-details-{{ $report->id }}">
                            <td colspan="5" class="bg-zinc-50 px-4 py-4 dark:bg-zinc-900/60">
                                <dl class="grid gap-x-6 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400 sm:grid-cols-[auto_1fr]">
                                    <dt class="font-medium">{{ __('Reported by') }}</dt>
                                    <dd>{{ $report->user?->name }} &lt;{{ $report->user?->email }}&gt;</dd>
                                    <dt class="font-medium">{{ __('Sent') }}</dt>
                                    <dd>{{ $report->created_at->toDayDateTimeString() }}</dd>
                                    @if ($report->isResolved())
                                        <dt class="font-medium">{{ __('Resolved') }}</dt>
                                        <dd>{{ $report->resolved_at->toDayDateTimeString() }}</dd>
                                    @endif
                                </dl>
                                <p class="ez-label mt-4 mb-1">{{ $report->type === \App\FeedbackType::Bug ? __('Steps to reproduce') : __('Details') }}</p>
                                <p class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200">{{ $report->details }}</p>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ $showResolved ? __('No reports found.') : __('No open reports. All clear.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
