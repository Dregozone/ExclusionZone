@php
    use App\Actions\Game\BuildManualCatalogue;

    $skillsHere = $rows->map(fn (array $row) => $row['action']->skill)->filter()->unique('id')->sortBy('display_name')->values();
    $toc = array_filter([
        'actions' => 'Actions',
        'enemies' => $enemies->isNotEmpty() ? 'Enemies' : null,
        'people' => $questSteps->isNotEmpty() ? 'People' : null,
        'routes' => 'Routes out',
    ]);
@endphp

<x-manual.layout
    :title="$city->city"
    :eyebrow="($city->country?->country ?? 'Unknown').' · '.($city->country?->continent ?? '')"
    :lede="ucfirst((string) $city->biome).'.'.($city->country?->notes ? ' '.$city->country->notes.'.' : '')"
    :breadcrumbs="['Locations' => route('manual.locations'), $city->city => null]"
    :toc="$toc"
>
    <x-slot:meta>
        <div class="flex flex-wrap gap-2">
            @if ($isStartingCity)<span class="rounded-sm border border-ez-signal/50 px-2 py-0.5 font-mono text-[11px] text-ez-signal uppercase">Every survivor starts here</span>@endif
            @if ($city->has_trade_post)<span class="rounded-sm border border-sky-400/50 px-2 py-0.5 font-mono text-[11px] text-sky-300 uppercase">Trade post</span>@endif
            @if ($city->radiation_damage > 0)<span class="rounded-sm border border-orange-500/60 px-2 py-0.5 font-mono text-[11px] text-orange-300 uppercase">Irradiated · −{{ $city->radiation_damage }} HP per cycle</span>@endif
            @if ($hasBrainScorcher)<span class="rounded-sm border border-fuchsia-400/50 px-2 py-0.5 font-mono text-[11px] text-fuchsia-300 uppercase">Brain Scorcher</span>@endif
        </div>
    </x-slot:meta>

    {{-- Conditions strip --}}
    <dl class="grid grid-cols-2 overflow-hidden rounded-sm border border-zinc-700 sm:grid-cols-4">
        @foreach ([
            ['Trouble chance', $city->trouble_chance_pct.'%', $city->trouble_chance_pct >= 60],
            ['Rain chance', $city->rain_chance_pct.'%', false],
            ['Avg temperature', $city->country?->avg_temp_c !== null ? $city->country->avg_temp_c.'°C' : '—', false],
            ['Loot tier', Str::title((string) $city->baseline_loot_tier), false],
        ] as [$label, $value, $danger])
            <div @class(['border-zinc-700 p-4', 'border-r' => ! $loop->last, 'max-sm:border-b' => $loop->index < 2, 'max-sm:border-r-0' => $loop->index === 1, 'bg-red-900/25' => $danger])>
                <dt class="ez-label">{{ $label }}</dt>
                <dd @class(['ez-numeral mt-2 text-4xl', 'text-red-300' => $danger, 'text-ez-ink' => ! $danger])>{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($hasBrainScorcher)
        <a href="{{ route('manual.guide', 'brain-scorcher') }}" class="ez-panel ez-heat group relative flex items-center gap-5 overflow-hidden p-5" wire:navigate>
            <img src="{{ asset('images/work/brain_scorcher.jpg') }}" alt="" class="size-20 shrink-0 rounded-sm object-cover sm:size-24" loading="lazy" />
            <div>
                <p class="ez-label !text-orange-300">Only here</p>
                <p class="mt-1 text-xl font-semibold text-ez-ink group-hover:text-ez-signal">The Brain Scorcher</p>
                <p class="mt-1 text-sm text-ez-muted">Burn items for a chance at something rarer. Read the odds →</p>
            </div>
        </a>
    @endif

    <x-manual.section id="actions" title="Actions" :intro="$rows->isEmpty() ? null : $rows->count().' '.Str::plural('job', $rows->count()).' training '.$skillsHere->count().' '.Str::plural('skill', $skillsHere->count()).'.'">
        @if ($skillsHere->isNotEmpty())
            <ul class="mb-4 flex flex-wrap gap-2">
                @foreach ($skillsHere as $skill)
                    <li><a href="{{ route('manual.skill', $skill->key) }}" class="rounded-sm border border-zinc-700 px-2.5 py-1 text-xs text-ez-ink transition hover:border-ez-signal/60 hover:text-ez-signal" wire:navigate>{{ $skill->display_name }}</a></li>
                @endforeach
            </ul>
        @endif
        <x-manual.action-table :rows="$rows" :show-location="false" :show-skill="true" empty="No actions here yet. It is a waypoint for now." />
    </x-manual.section>

    @if ($enemies->isNotEmpty())
        <x-manual.section id="enemies" title="Enemies" intro="Fought through this city’s combat actions.">
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($enemies as $enemy)
                    <div class="ez-card ez-pin ez-pin-heat p-4 pt-5">
                        <p class="flex justify-between gap-3"><span class="font-semibold text-ez-ink">{{ $enemy->name }}</span><span class="font-mono text-sm text-ez-muted">{{ $enemy->health }} HP</span></p>
                        <p class="mt-1 text-xs text-ez-muted">{{ $enemy->skill?->display_name }} · hits {{ $enemy->min_damage }}–{{ $enemy->max_damage }} · {{ $enemy->xp_reward }} XP</p>
                    </div>
                @endforeach
            </div>
        </x-manual.section>
    @endif

    @if ($questSteps->isNotEmpty())
        <x-manual.section id="people" title="People" intro="Contacts you will meet here on quests and jobs.">
            <ul class="grid gap-3 sm:grid-cols-2">
                @foreach ($questSteps->groupBy('person_of_interest') as $person => $steps)
                    <li class="ez-paper p-4 pt-6">
                        <p class="text-lg font-semibold">{{ $person }}</p>
                        <p class="mt-1 text-sm text-ez-paper-muted">{{ $steps->pluck('name')->unique()->implode(' · ') }}</p>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-sm"><a href="{{ route('manual.quests') }}" class="text-ez-signal hover:underline" wire:navigate>See all quests →</a></p>
        </x-manual.section>
    @endif

    <x-manual.section id="routes" title="Routes out" intro="Travel times are for clear weather and a rested survivor.">
        @if ($city->neighbors->isEmpty())
            <p class="text-sm text-ez-muted">No routes lead out of here yet.</p>
        @else
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($city->neighbors->sortBy('pivot.duration_seconds') as $neighbor)
                    <li>
                        <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($neighbor)) }}" class="ez-card group flex items-center justify-between gap-3 p-4 transition hover:border-ez-signal/60" wire:navigate>
                            <span class="min-w-0">
                                <span class="block truncate text-lg font-semibold text-ez-ink group-hover:text-ez-signal">{{ $neighbor->city }}</span>
                                <span class="block text-xs text-ez-muted">{{ $neighbor->country?->country }} · trouble {{ $neighbor->trouble_chance_pct }}%</span>
                            </span>
                            <span class="shrink-0 text-right">
                                <flux:icon.arrow-long-right variant="micro" class="ml-auto text-zinc-500" />
                                <x-manual.duration :seconds="max(10, (int) ($neighbor->pivot->duration_seconds ?? 10))" class="text-sm text-ez-signal" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-manual.section>
</x-manual.layout>
