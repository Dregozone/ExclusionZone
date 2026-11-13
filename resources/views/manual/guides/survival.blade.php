@php
    use App\Actions\Game\BuildManualCatalogue;

    $maximum = (int) config('survival.maximum');
@endphp

<x-manual.layout
    :title="$guide['title']"
    eyebrow="Guide 04 · Condition"
    lede="Three meters decide how fast you work and whether you live: hunger, fatigue and health. None of them stop you working, but ignoring them costs time, then health."
    :image="$guide['image']"
    :breadcrumbs="['Guides' => route('manual.guide', 'getting-started'), $guide['title'] => null]"
    :toc="['meters' => 'The three meters', 'food' => 'Food', 'healing' => 'Healing', 'weather' => 'Weather', 'hazards' => 'Radiation & accidents', 'death' => 'Death']"
>
    <x-manual.section id="meters" title="The three meters" intro="Hunger and fatigue rise from 0 to {{ $maximum }} as you work and travel. Health falls from {{ $maximum }}. Cross a line and your timers slow down.">
        <div class="grid gap-4 lg:grid-cols-3">
            @foreach (['hunger' => ['Hunger', 'Rises with every work cycle and travel leg. Eat food to bring it down.', 'text-amber-400'], 'fatigue' => ['Fatigue', 'Rises with work and travel. Falls by itself, about '.(float) config('survival.rest.fatigue_recovered_per_minute').' a minute, whenever you are not working.', 'text-amber-400'], 'health' => ['Health', 'Falls from fights, accidents, storms, radiation and starving. Comes back slowly by itself, faster with food and medical items.', 'text-red-400']] as $meter => [$label, $copy, $tone])
                <div class="ez-card p-5">
                    <p class="ez-display text-3xl text-ez-ink">{{ $label }}</p>
                    <p class="mt-2 text-sm leading-6 text-ez-muted">{{ $copy }}</p>
                    {{-- Threshold strip --}}
                    <div class="relative mt-5 h-3">
                        <div class="ez-meter h-3 text-zinc-600"><div class="ez-meter-fill w-full"></div></div>
                        @foreach ($thresholds[$meter] as $threshold)
                            <span class="absolute top-0 h-3 w-0.5 {{ $meter === 'health' ? 'bg-red-400' : 'bg-amber-400' }}" style="left: {{ $threshold['at'] / $maximum * 100 }}%"></span>
                        @endforeach
                    </div>
                    <ul class="mt-4 space-y-1.5 text-sm">
                        @foreach ($thresholds[$meter] as $threshold)
                            <li class="flex items-center justify-between gap-3">
                                <span class="text-ez-ink">{{ $threshold['label'] }} <span class="font-mono text-xs text-zinc-500">{{ $meter === 'health' ? '≤' : '≥' }} {{ $threshold['at'] }}</span></span>
                                <span class="font-mono text-xs {{ $tone }}">timers +{{ $threshold['timer_increase_percent'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <x-manual.callout type="danger" class="mt-4" title="Starving or spent">
            At {{ $maximum }} hunger (starving) or {{ $maximum }} fatigue (spent), every finished cycle, travel leg or Brain Scorcher burn costs
            {{ config('survival.health.lost_while_starving') }} health, and you stop healing on your own. Eat and rest before you get there.
        </x-manual.callout>
    </x-manual.section>

    <x-manual.section id="food" title="Food" intro="Eat from the Rations panel or your inventory. You can eat while working. Bigger numbers are better.">
        <div class="ez-panel overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-700 text-left">
                        <th scope="col" class="ez-label px-4 py-3 font-normal">Food</th>
                        <th scope="col" class="ez-label px-4 py-3 text-right font-normal">Hunger</th>
                        <th scope="col" class="ez-label px-4 py-3 text-right font-normal">Fatigue</th>
                        <th scope="col" class="ez-label px-4 py-3 text-right font-normal">Health</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($foods as $food)
                        <tr class="border-b border-zinc-800 last:border-0">
                            <td class="px-4 py-2.5"><x-manual.item :item="$food" /></td>
                            <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ $food->hunger_restored ? 'text-ez-signal' : 'text-zinc-600' }}">{{ $food->hunger_restored ? '−'.$food->hunger_restored : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ $food->fatigue_restored ? 'text-ez-signal' : 'text-zinc-600' }}">{{ $food->fatigue_restored ? '−'.$food->fatigue_restored : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono tabular-nums {{ $food->health_restored ? 'text-emerald-300' : 'text-zinc-600' }}">{{ $food->health_restored ? '+'.$food->health_restored : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-manual.section>

    <x-manual.section id="healing" title="Healing" intro="Health comes back by itself at about {{ config('survival.rest.health_recovered_per_minute') }} a minute unless you are starving. For faster healing:">
        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <p class="ez-label">Medical items · press Use</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($medical as $item)
                        <li class="ez-card flex items-center justify-between gap-3 p-3">
                            <x-manual.item :item="$item" size="md" />
                            <span class="font-mono text-sm text-emerald-300">+{{ $item->health_restored }} health</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="ez-label">Clinics · city actions that heal each cycle</p>
                <ul class="mt-3 space-y-2">
                    @forelse ($healingRows as $row)
                        <li class="ez-card flex flex-wrap items-center justify-between gap-3 p-3">
                            <span>
                                <span class="block font-medium text-ez-ink">{{ $row['action']->label }}</span>
                                <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($row['action']->city)) }}#action-{{ $row['action']->action_key }}" class="text-xs text-ez-signal hover:underline" wire:navigate>{{ $row['action']->city->city }}</a>
                                @foreach ($row['inputs'] as $input)
                                    <span class="text-xs text-ez-muted">· uses {{ $input['quantity'] }}× {{ $input['item']?->name ?? $input['key'] }}</span>
                                @endforeach
                            </span>
                            <span class="font-mono text-sm text-emerald-300">+{{ $row['health'] }} / cycle</span>
                        </li>
                    @empty
                        <li class="text-sm text-ez-muted">No clinics yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </x-manual.section>

    <x-manual.section id="weather" title="Weather" intro="Each city rolls its weather from its rain chance. The weather where you start a job or journey applies to the whole cycle.">
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($weathers as $weather)
                <div @class(['ez-card p-5', 'ez-heat' => $weather->value === 'storm'])>
                    <flux:icon :name="match ($weather->value) { 'rain' => 'cloud', 'storm' => 'bolt', default => 'sun' }" class="{{ $weather->isBad() ? 'text-amber-400' : 'text-ez-signal' }}" />
                    <p class="ez-display mt-2 text-3xl text-ez-ink">{{ $weather->label() }}</p>
                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-ez-muted">Timers</dt><dd class="font-mono text-ez-ink">+{{ $weather->timerIncreasePercent() }}%</dd></div>
                        <div class="flex justify-between"><dt class="text-ez-muted">Loot lost</dt><dd class="font-mono text-ez-ink">{{ $weather->itemLossChancePercent() }}%</dd></div>
                        <div class="flex justify-between"><dt class="text-ez-muted">Bonus XP</dt><dd class="font-mono text-ez-signal">+{{ $weather->experienceBonusPercent() }}%</dd></div>
                    </dl>
                    @if ($weather->value === 'storm')
                        <p class="mt-3 text-xs text-red-300">{{ config('survival.environment.storm_harm_chance_pct') }}% chance per cycle or leg to lose {{ config('survival.environment.storm_damage') }} health.</p>
                    @endif
                </div>
            @endforeach
        </div>
    </x-manual.section>

    <x-manual.section id="hazards" title="Radiation & accidents">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="ez-card ez-heat p-5">
                <p class="flex items-center gap-2 font-semibold text-ez-ink"><flux:icon.exclamation-triangle variant="mini" class="text-orange-400" /> Irradiated cities</p>
                <p class="mt-2 text-sm leading-6 text-ez-muted">Every cycle worked here, and every arrival, costs health.</p>
                <ul class="mt-3 space-y-1.5">
                    @forelse ($irradiated as $city)
                        <li class="flex justify-between text-sm">
                            <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($city)) }}" class="text-ez-signal hover:underline" wire:navigate>{{ $city->city }}</a>
                            <span class="font-mono text-red-300">−{{ $city->radiation_damage }} HP</span>
                        </li>
                    @empty
                        <li class="text-sm text-ez-muted">None right now.</li>
                    @endforelse
                </ul>
            </div>
            <div class="ez-card p-5">
                <p class="flex items-center gap-2 font-semibold text-ez-ink"><flux:icon.wrench variant="mini" class="text-amber-400" /> Accidents</p>
                <p class="mt-2 text-sm leading-6 text-ez-muted">
                    Some high-risk actions have an accident chance each cycle. The skill and location pages show it in the Risk column, for example
                    <span class="font-mono text-red-300">20% · −10 HP</span>.
                </p>
            </div>
        </div>
    </x-manual.section>

    <x-manual.section id="death" title="Death" intro="At 0 health you go down. It is a setback, not the end.">
        <div class="ez-paper max-w-2xl p-6 pt-8">
            <span class="ez-stamp float-right text-sm">Recovered</span>
            <ol class="list-decimal space-y-2 pl-5 text-sm leading-6">
                <li>Your work stops.</li>
                <li>Someone drags you to the nearest trade post on your continent{{ $tradePosts->isNotEmpty() ? ' ('.$tradePosts->pluck('city')->implode(', ').')' : '' }}.</li>
                <li>Scavengers take about {{ config('survival.death.pack_lost_percent') }}% of every stack in your pack. Quest items are spared.</li>
                <li>You wake with {{ config('survival.health.revive_health') }} health and cannot work or travel for {{ config('survival.death.recovery_minutes') }} minutes.</li>
            </ol>
        </div>
    </x-manual.section>

    <x-manual.pager :previous="$previous" :next="$next" />
</x-manual.layout>
