@php
    use App\Actions\Game\BuildManualCatalogue;
@endphp

<x-manual.layout
    title="Locations"
    lede="Every city still standing, grouped by continent. Each page lists its actions, enemies, people and routes out."
    :breadcrumbs="['Locations' => null]"
>
    <div class="flex flex-wrap items-center gap-3 text-sm">
        <span class="ez-label">Key</span>
        <span class="inline-flex items-center gap-1.5 text-ez-ink"><span class="rounded-sm border border-ez-signal/50 px-1.5 font-mono text-[10px] text-ez-signal uppercase">Start</span> where you begin</span>
        <span class="inline-flex items-center gap-1.5 text-ez-ink"><span class="rounded-sm border border-sky-400/50 px-1.5 font-mono text-[10px] text-sky-300 uppercase">Trade post</span> trade &amp; revive</span>
        <span class="inline-flex items-center gap-1.5 text-ez-ink"><span class="rounded-sm border border-orange-500/60 px-1.5 font-mono text-[10px] text-orange-300 uppercase">Radiation</span> costs health</span>
        <a href="{{ route('world-map') }}" class="ml-auto text-ez-signal hover:underline" wire:navigate>Open the world map →</a>
    </div>

    @foreach ($continents as $continent => $cities)
        <x-manual.section :id="Str::slug($continent)" :eyebrow="$cities->count().' '.Str::plural('city', $cities->count())" :title="$continent">
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cities as $city)
                    <li>
                        <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($city)) }}" @class(['ez-card group flex h-full flex-col p-4 transition hover:border-ez-signal/60', 'ez-heat' => $city->radiation_damage > 0]) wire:navigate>
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="ez-display text-3xl text-ez-ink group-hover:text-ez-signal">{{ $city->city }}</p>
                                    <p class="text-xs text-ez-muted">{{ $city->country?->country }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    @if ($city->city === 'Kyiv')<span class="rounded-sm border border-ez-signal/50 px-1.5 font-mono text-[10px] text-ez-signal uppercase">Start</span>@endif
                                    @if ($city->has_trade_post)<span class="rounded-sm border border-sky-400/50 px-1.5 font-mono text-[10px] text-sky-300 uppercase">Trade post</span>@endif
                                    @if ($city->radiation_damage > 0)<span class="rounded-sm border border-orange-500/60 px-1.5 font-mono text-[10px] text-orange-300 uppercase">Radiation</span>@endif
                                </div>
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm text-ez-muted">{{ ucfirst((string) $city->biome) }}</p>
                            <dl class="mt-auto grid grid-cols-3 gap-2 border-t border-zinc-800 pt-3 text-xs">
                                <div><dt class="ez-label !text-[9px]">Actions</dt><dd class="mt-1 font-mono text-ez-ink">{{ $city->actions_count }}</dd></div>
                                <div><dt class="ez-label !text-[9px]">Trouble</dt><dd @class(['mt-1 font-mono', 'text-red-300' => $city->trouble_chance_pct >= 60, 'text-ez-ink' => $city->trouble_chance_pct < 60])>{{ $city->trouble_chance_pct }}%</dd></div>
                                <div><dt class="ez-label !text-[9px]">Enemies</dt><dd class="mt-1 font-mono text-ez-ink">{{ $enemyCounts[$city->id] ?? 0 }}</dd></div>
                            </dl>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-manual.section>
    @endforeach
</x-manual.layout>
