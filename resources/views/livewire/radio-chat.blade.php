<div class="flex h-full min-h-[300px] flex-col">
    <div class="flex shrink-0 items-center gap-2 border-b border-zinc-800/80 px-4 py-2">
        <div class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.85)]" aria-hidden="true"></div>
        <p class="font-mono text-[10px] uppercase tracking-[0.3em] text-zinc-500">Channel 7 · Exclusion Net</p>
        @if ($this->canUseRadio)
            <a
                href="{{ route('radio.history') }}"
                target="_blank"
                rel="noopener"
                class="ml-auto flex shrink-0 items-center gap-1 rounded border border-zinc-800 px-2 py-1 font-mono text-[9px] uppercase tracking-wide text-zinc-400 transition-colors hover:border-emerald-500/40 hover:text-emerald-400 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-emerald-500"
            >
                Today's log
                <flux:icon.arrow-top-right-on-square variant="micro" class="size-3" />
            </a>
        @endif
    </div>

    @if ($this->canUseRadio)
        <div
            x-data="{ scrollToLatest() { this.$el.scrollTop = this.$el.scrollHeight } }"
            x-init="scrollToLatest(); new MutationObserver(() => scrollToLatest()).observe($el, { childList: true, subtree: true })"
            x-intersect="scrollToLatest()"
            class="min-h-0 flex-1 space-y-2 overflow-y-auto overscroll-contain px-3 py-3"
            aria-live="polite"
        >
            @forelse ($this->messages as $radioMessage)
                <x-radio-message
                    wire:key="radio-message-{{ $radioMessage->id }}"
                    :message="$radioMessage"
                    :is-own="$radioMessage->user_id === auth()->id()"
                />
            @empty
                <div class="flex h-full flex-col items-center justify-center py-10 text-center">
                    <p class="font-mono text-xs text-zinc-500">No transmissions detected.</p>
                    <p class="mt-1 font-mono text-xs text-zinc-700">Key the mic to break radio silence.</p>
                </div>
            @endforelse
        </div>

        <form wire:submit="send" class="shrink-0 border-t border-zinc-800/80 bg-zinc-900/50 p-2">
            <div class="flex items-center gap-2">
                <label for="radio-message" class="sr-only">Message</label>
                <input
                    id="radio-message"
                    type="text"
                    wire:model="message"
                    maxlength="{{ \App\Models\RadioMessage::MAX_LENGTH }}"
                    autocomplete="off"
                    placeholder="Transmit on channel 7…"
                    class="min-w-0 flex-1 rounded-full border border-zinc-800 bg-zinc-950 px-4 py-2 text-sm text-zinc-200 placeholder:text-zinc-600 focus:border-emerald-500/60 focus:outline-none focus:ring-1 focus:ring-emerald-500/60"
                />
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white transition-colors hover:bg-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 disabled:opacity-50"
                    aria-label="Send message"
                >
                    <flux:icon.paper-airplane variant="micro" />
                </button>
            </div>
            @error('message')
                <p class="mt-1.5 px-2 font-mono text-[10px] text-red-400">{{ $message }}</p>
            @enderror
        </form>
    @else
        <div class="flex flex-1 flex-col items-center justify-center p-6 text-center">
            <p class="font-mono text-xs text-zinc-500">{{ auth()->user()->denialReasonForTask('chat_send') }}</p>
        </div>
    @endif
</div>
