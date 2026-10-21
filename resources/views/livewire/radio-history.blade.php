<section
    x-data="{
        search: '',
        matches(text) {
            const term = this.search.trim().toLowerCase();

            return term === '' || text.includes(term);
        },
        get hasMatches() {
            this.search;

            return [...this.$root.querySelectorAll('[data-search]')].some(el => this.matches(el.dataset.search));
        },
    }"
    class="mx-auto flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950"
>
    <div class="sticky top-0 z-10 space-y-2 border-b border-zinc-800/80 bg-zinc-900/95 px-4 py-3 backdrop-blur">
        <div class="flex items-center gap-2">
            <div class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.85)]" aria-hidden="true"></div>
            <h1 class="font-mono text-xs uppercase tracking-[0.3em] text-zinc-400">Radio log · {{ today()->format('d M Y') }}</h1>
            <span class="ml-auto font-mono text-[10px] text-zinc-600">{{ trans_choice(':count transmission|:count transmissions', $this->messages->count()) }}</span>
        </div>
        <label for="radio-search" class="sr-only">Search today's transmissions</label>
        <input
            id="radio-search"
            type="search"
            x-model="search"
            autofocus
            autocomplete="off"
            placeholder="Search callsigns or messages…"
            class="w-full rounded-full border border-zinc-800 bg-zinc-950 px-4 py-1.5 text-sm text-zinc-200 placeholder:text-zinc-600 focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/60"
        />
    </div>

    <div class="space-y-1.5 px-3 py-3">
        @forelse ($this->messages as $radioMessage)
            <div
                wire:key="radio-log-{{ $radioMessage->id }}"
                data-search="{{ Str::lower($radioMessage->user?->name.' '.$radioMessage->body) }}"
                x-show="matches($el.dataset.search)"
            >
                <x-radio-message
                    :message="$radioMessage"
                    :is-own="$radioMessage->user_id === auth()->id()"
                    compact
                />
            </div>
        @empty
            <p class="py-10 text-center font-mono text-xs text-zinc-500">No transmissions logged today.</p>
        @endforelse

        @if ($this->messages->isNotEmpty())
            <p x-show="! hasMatches" x-cloak class="py-10 text-center font-mono text-xs text-zinc-500">
                No transmissions match "<span x-text="search"></span>".
            </p>
        @endif
    </div>
</section>
