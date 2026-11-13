@props([
    'actions' => [],
])

{{-- $actions: list of ['label' => string, 'xp' => int, 'level' => int] used to show how many cycles each would take. --}}
<div
    x-data="{
        from: 1,
        to: 10,
        actions: @js($actions),
        total(level) { level = Math.max(1, Math.floor(level || 1)); return {{ \App\Actions\Game\BuildManualCatalogue::XP_PER_LEVEL_STEP }} * level * (level - 1) / 2 },
        get needed() { return Math.max(0, this.total(this.to) - this.total(this.from)) },
        cycles(xp) { return xp > 0 ? Math.ceil(this.needed / xp) : '—' },
    }"
    {{ $attributes->class(['ez-panel p-5']) }}
>
    <div class="flex flex-wrap items-end gap-4">
        <label class="grid gap-1">
            <span class="ez-label">From level</span>
            <input type="number" min="1" max="200" x-model.number="from" class="w-24 rounded-sm border border-zinc-700 bg-ez-ground px-3 py-2 font-mono text-ez-ink focus:border-ez-signal focus:outline-none" />
        </label>
        <label class="grid gap-1">
            <span class="ez-label">To level</span>
            <input type="number" min="1" max="200" x-model.number="to" class="w-24 rounded-sm border border-zinc-700 bg-ez-ground px-3 py-2 font-mono text-ez-ink focus:border-ez-signal focus:outline-none" />
        </label>
        <div class="ml-auto text-right" aria-live="polite">
            <p class="ez-label">XP needed</p>
            <p class="ez-numeral text-5xl text-ez-signal" x-text="needed.toLocaleString()"></p>
        </div>
    </div>

    @if ($actions !== [])
        <div class="mt-5 border-t border-zinc-800 pt-4">
            <p class="ez-label">Cycles needed at clear weather</p>
            <ul class="mt-2 grid gap-1.5 sm:grid-cols-2">
                <template x-for="action in actions" :key="action.label">
                    <li class="flex items-center justify-between gap-3 rounded-sm px-2 py-1 text-sm" :class="action.level > from ? 'text-zinc-500' : 'text-ez-ink'">
                        <span class="min-w-0 truncate"><span x-text="action.label"></span> <span class="font-mono text-xs text-zinc-500" x-text="`· lv ${action.level} · ${action.xp} XP`"></span></span>
                        <span class="shrink-0 font-mono tabular-nums" x-text="cycles(action.xp).toLocaleString()"></span>
                    </li>
                </template>
            </ul>
            <p class="mt-2 text-xs text-zinc-500">Greyed-out actions need a higher level than your starting level.</p>
        </div>
    @endif
</div>
