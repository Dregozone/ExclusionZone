@php
    use App\Actions\Game\BuildManualCatalogue;

    $cities = $rows->map(fn (array $row) => $row['action']->city)->unique('id')->sortBy('city')->values();
    $unlockLevels = $rows->map(fn (array $row) => $row['action']->min_level)->unique()->sort()->values();
    $produces = $rows->pluck('output')->filter()->unique('key')->values();
    $guideLink = match (true) {
        in_array($skill->key, ['combat_melee', 'combat_ranged'], true) => ['combat', 'Read the Fights guide'],
        in_array($skill->key, ['cooking', 'medicine', 'survival'], true) => ['survival', 'Read Staying alive'],
        $skill->key === 'barter' => ['trading-and-radio', 'Read Trading & the radio'],
        default => ['skills-and-work', 'Read Skills & work loops'],
    };
    $calculatorActions = $rows->map(fn (array $row): array => ['label' => $row['action']->label.' ('.$row['action']->city->city.')', 'xp' => $row['xp'], 'level' => $row['action']->min_level])->values()->all();
    $toc = array_filter([
        'training' => 'Training ladder',
        'where' => 'Where to train',
        'enemies' => $enemies->isNotEmpty() || $troubleTypes->isNotEmpty() ? 'Enemies' : null,
        'calculator' => 'XP calculator',
        'more' => 'Other skills',
    ]);
@endphp

<x-manual.layout
    :title="$skill->display_name"
    eyebrow="Skill manual"
    :lede="$skill->description.'.'"
    :image="$image"
    :breadcrumbs="['Skills' => route('manual.skills'), $skill->display_name => null]"
    :toc="$toc"
>
    <x-slot:meta>
        <dl class="flex flex-wrap gap-x-8 gap-y-3">
            <div><dt class="ez-label">Actions</dt><dd class="ez-numeral mt-1 text-4xl text-ez-ink">{{ $rows->count() }}</dd></div>
            <div><dt class="ez-label">Cities</dt><dd class="ez-numeral mt-1 text-4xl text-ez-ink">{{ $cities->count() }}</dd></div>
            <div><dt class="ez-label">Makes</dt><dd class="ez-numeral mt-1 text-4xl text-ez-ink">{{ $produces->count() }}</dd></div>
        </dl>
    </x-slot:meta>

    {{-- Quick switch between skills --}}
    <nav aria-label="Switch skill" class="-mt-6 flex items-center gap-3">
        <label for="skill-switch" class="ez-label shrink-0">Jump to skill</label>
        <select
            id="skill-switch"
            onchange="window.location.href = this.value"
            class="w-full max-w-xs rounded-sm border border-zinc-700 bg-ez-ground px-3 py-2 text-sm text-ez-ink focus:border-ez-signal focus:outline-none"
        >
            @foreach ($allSkills as $option)
                <option value="{{ route('manual.skill', $option->key) }}" @selected($option->id === $skill->id)>{{ $option->display_name }}</option>
            @endforeach
        </select>
    </nav>

    <x-manual.section id="training" title="Training ladder" intro="Every action that gives {{ $skill->display_name }} XP, lowest level first. Set your level to see what you can do now and what opens up next. Times are at level 1 in clear weather; each level shaves 1% off.">
        <div
            x-data="{
                level: {{ (int) ($playerLevel ?? 1) }},
                isPlayer: {{ $playerLevel !== null ? 'true' : 'false' }},
                unlocks: @js($unlockLevels->values()),
                total(level) { level = Math.max(1, Math.floor(level || 1)); return {{ BuildManualCatalogue::XP_PER_LEVEL_STEP }} * level * (level - 1) / 2 },
                get next() { return this.unlocks.find((unlock) => unlock > this.level) ?? null },
                get unlockedCount() { return @js($rows->map(fn ($row) => $row['action']->min_level)->values()).filter((needed) => needed <= this.level).length },
                xpTo(target) { return Math.max(0, this.total(target) - this.total(this.level) - {{ (int) ($playerXp ?? 0) }} * (this.isPlayer && this.level === {{ (int) ($playerLevel ?? 1) }} ? 1 : 0)) },
                state(needed) { return needed <= this.level ? 'unlocked' : (needed === this.next ? 'next' : 'locked') },
            }"
            class="space-y-6"
        >
            {{-- Your level --}}
            <div class="ez-panel grid gap-4 p-4 sm:grid-cols-[auto_1fr_auto] sm:items-center">
                <label class="flex items-center gap-3">
                    <span class="ez-label">{{ $playerLevel !== null ? 'Your level' : 'My level' }}</span>
                    <button type="button" @click="level = Math.max(1, level - 1); isPlayer = false" class="size-9 rounded-sm border border-zinc-700 text-ez-ink hover:border-ez-signal" aria-label="Lower level">−</button>
                    <input type="number" min="1" max="200" x-model.number="level" @input="isPlayer = false" class="w-16 rounded-sm border border-zinc-700 bg-ez-ground px-2 py-1.5 text-center font-mono text-lg text-ez-signal focus:border-ez-signal focus:outline-none" />
                    <button type="button" @click="level = Math.min(200, level + 1); isPlayer = false" class="size-9 rounded-sm border border-zinc-700 text-ez-ink hover:border-ez-signal" aria-label="Raise level">+</button>
                </label>
                <p class="text-sm text-ez-muted" aria-live="polite">
                    <span class="text-ez-ink" x-text="`${unlockedCount} of {{ $rows->count() }}`"></span> actions unlocked.
                    <template x-if="next !== null">
                        <span>Next unlock at <span class="font-mono text-ez-signal" x-text="`level ${next}`"></span>, <span class="font-mono text-ez-ink" x-text="xpTo(next).toLocaleString()"></span> XP away.</span>
                    </template>
                    <template x-if="next === null && unlockedCount > 0"><span class="text-ez-safe">Everything is open to you.</span></template>
                </p>
                @if ($playerLevel !== null)
                    <p class="font-mono text-[11px] text-zinc-500 sm:text-right">Read from your survivor<br class="hidden sm:inline"> · {{ number_format($playerXp) }} / {{ number_format(BuildManualCatalogue::experienceToNextLevel($playerLevel)) }} XP</p>
                @else
                    <p class="text-xs text-zinc-500 sm:text-right"><a href="{{ route('login') }}" class="text-ez-signal hover:underline">Log in</a> to fill this in for you.</p>
                @endif
            </div>

            @if ($unlockLevels->count() > 1)
                <nav aria-label="Jump to level" class="flex flex-wrap items-center gap-1.5">
                    <span class="ez-label mr-1">Jump to level</span>
                    @foreach ($unlockLevels as $unlockLevel)
                        <a
                            href="#level-{{ $unlockLevel }}"
                            class="rounded-full border px-2.5 py-0.5 font-mono text-xs transition"
                            :class="{
                                'border-ez-safe/60 bg-ez-safe/10 text-ez-safe': state({{ $unlockLevel }}) === 'unlocked',
                                'border-amber-400/70 bg-amber-400/10 text-amber-300': state({{ $unlockLevel }}) === 'next',
                                'border-zinc-700 text-ez-muted hover:text-ez-ink': state({{ $unlockLevel }}) === 'locked',
                            }"
                        >{{ $unlockLevel }}</a>
                    @endforeach
                </nav>
            @endif

            @forelse ($rows->groupBy(fn (array $row) => $row['action']->min_level) as $needed => $levelRows)
                <div
                    id="level-{{ $needed }}"
                    class="scroll-mt-24 transition"
                    :class="state({{ $needed }}) === 'locked' ? 'opacity-55' : ''"
                >
                    <div class="mb-3 flex flex-wrap items-center gap-3">
                        <h3 class="ez-display text-3xl text-ez-ink">Level {{ $needed }}</h3>
                        <span x-show="state({{ $needed }}) === 'unlocked'" class="inline-flex items-center gap-1 rounded-sm border border-ez-safe/50 bg-ez-safe/10 px-2 py-0.5 font-mono text-[10px] tracking-wider text-ez-safe uppercase"><flux:icon.check variant="micro" /> Unlocked</span>
                        <span x-show="state({{ $needed }}) === 'next'" x-cloak class="inline-flex items-center gap-1 rounded-sm border border-amber-400/60 bg-amber-400/10 px-2 py-0.5 font-mono text-[10px] tracking-wider text-amber-300 uppercase"><flux:icon.arrow-up variant="micro" /> Next up · <span x-text="`${xpTo({{ $needed }}).toLocaleString()} XP`"></span></span>
                        <span x-show="state({{ $needed }}) === 'locked'" x-cloak class="inline-flex items-center gap-1 rounded-sm border border-zinc-600 px-2 py-0.5 font-mono text-[10px] tracking-wider text-ez-muted uppercase"><flux:icon.lock-closed variant="micro" /> Locked · <span x-text="`${xpTo({{ $needed }}).toLocaleString()} XP`"></span></span>
                        <span class="h-px flex-1 bg-zinc-800" aria-hidden="true"></span>
                    </div>
                    <x-manual.action-table :rows="$levelRows->values()" />
                </div>
            @empty
                <p class="ez-card p-5 text-sm text-ez-muted">No city action trains {{ $skill->display_name }} yet. It grows through trouble fights and quest rewards.</p>
            @endforelse
        </div>
        <p class="mt-4 text-sm"><a href="{{ route('manual.guide', $guideLink[0]) }}" class="text-ez-signal hover:underline" wire:navigate>{{ $guideLink[1] }} →</a></p>
    </x-manual.section>

    <x-manual.section id="where" title="Where to train">
        @if ($cities->isEmpty())
            <p class="text-sm text-ez-muted">No city has a {{ $skill->display_name }} action yet.</p>
        @else
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cities as $city)
                    @php($cityRows = $rows->filter(fn ($row) => $row['action']->city_id === $city->id))
                    <li>
                        <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($city)) }}" class="ez-card ez-pin group block p-4 pt-5 transition hover:border-ez-signal/60" wire:navigate>
                            <p class="ez-display text-3xl text-ez-ink group-hover:text-ez-signal">{{ $city->city }}</p>
                            <p class="text-xs text-ez-muted">{{ $city->country?->country }} · trouble {{ $city->trouble_chance_pct }}%</p>
                            <p class="mt-2 text-sm text-ez-ink">{{ $cityRows->pluck('action.label')->implode(', ') }}</p>
                            <p class="mt-1 font-mono text-xs text-ez-signal">from level {{ $cityRows->min(fn ($row) => $row['action']->min_level) }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($produces->isNotEmpty())
            <p class="ez-label mt-6">{{ $skill->display_name }} makes</p>
            <ul class="mt-2 flex flex-wrap gap-2">
                @foreach ($produces as $output)
                    <li><x-manual.item :item="$output['item']" :key="$output['key']" size="md" /></li>
                @endforeach
            </ul>
        @endif
    </x-manual.section>

    @if ($enemies->isNotEmpty() || $troubleTypes->isNotEmpty())
        <x-manual.section id="enemies" title="Enemies" intro="What this skill is tested against.">
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($enemies as $enemy)
                    <div class="ez-card ez-heat p-4">
                        <p class="flex justify-between gap-3"><span class="font-semibold text-ez-ink">{{ $enemy->name }}</span><span class="font-mono text-sm text-ez-muted">{{ $enemy->health }} HP</span></p>
                        <p class="mt-1 text-xs text-ez-muted">Fought in <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($enemy->city)) }}" class="text-ez-signal hover:underline" wire:navigate>{{ $enemy->city->city }}</a> · hits {{ $enemy->min_damage }}–{{ $enemy->max_damage }} · {{ $enemy->xp_reward }} XP</p>
                    </div>
                @endforeach
                @foreach ($troubleTypes as $type)
                    <div class="ez-card p-4">
                        <p class="flex justify-between gap-3"><span class="font-semibold text-ez-ink">{{ $type->name }}</span><span class="font-mono text-sm text-orange-300">level {{ $type->fighter_level }}</span></p>
                        <p class="mt-1 text-xs text-ez-muted">Trouble on arrival · hits {{ $type->damage_min }}–{{ $type->damage_max }} · {{ $type->xp_reward }} XP on a win</p>
                    </div>
                @endforeach
            </div>
        </x-manual.section>
    @endif

    <x-manual.section id="calculator" title="XP calculator" intro="Enter your level and a goal to see the XP and how many cycles of each action it takes.">
        <x-manual.xp-calculator :actions="$calculatorActions" />
    </x-manual.section>

    <x-manual.section id="more" title="Other skills">
        <ul class="flex flex-wrap gap-2">
            @foreach ($otherSkills as $other)
                <li><a href="{{ route('manual.skill', $other->key) }}" class="ez-card inline-block px-3 py-2 text-sm text-ez-ink transition hover:border-ez-signal/60 hover:text-ez-signal" wire:navigate>{{ $other->display_name }}</a></li>
            @endforeach
        </ul>
    </x-manual.section>
</x-manual.layout>
