@props([
    'rows',
    'showLocation' => true,
    'showSkill' => false,
    'empty' => 'No actions use this yet.',
])

@php
    use App\Actions\Game\BuildManualCatalogue;
@endphp

@if ($rows->isEmpty())
    <p class="ez-card p-5 text-sm text-ez-muted">{{ $empty }}</p>
@else
    {{-- Desktop: a scannable table --}}
    <div class="ez-panel hidden overflow-x-auto lg:block">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-700">
                    <th scope="col" class="ez-label px-4 py-3 font-normal">Level</th>
                    <th scope="col" class="ez-label px-4 py-3 font-normal">Action</th>
                    <th scope="col" class="ez-label px-4 py-3 font-normal">Needs → Gives</th>
                    <th scope="col" class="ez-label px-4 py-3 text-right font-normal">XP</th>
                    <th scope="col" class="ez-label px-4 py-3 text-right font-normal">Time</th>
                    <th scope="col" class="ez-label px-4 py-3 font-normal">Risk</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php($action = $row['action'])
                    <tr id="action-{{ $action->action_key }}" class="scroll-mt-24 border-b border-zinc-800 align-top last:border-0 target:bg-ez-signal/10">
                        <td class="px-4 py-4">
                            <span class="inline-flex min-w-9 justify-center rounded-sm border border-ez-signal/40 px-1.5 py-0.5 font-mono text-base font-semibold text-ez-signal">{{ $action->min_level }}</span>
                        </td>
                        <td class="min-w-56 px-4 py-4">
                            <p class="font-medium text-ez-ink">{{ $action->label }}</p>
                            <p class="mt-1 text-xs text-ez-muted">
                                @if ($showSkill)
                                    <a href="{{ route('manual.skill', $action->skill_key) }}" class="text-ez-signal hover:underline" wire:navigate>{{ $action->skill?->display_name ?? $action->skill_key }}</a>
                                @endif
                                @if ($showSkill && $showLocation) · @endif
                                @if ($showLocation)
                                    <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($action->city)) }}" class="text-ez-signal hover:underline" wire:navigate>{{ $action->city->city }}</a>
                                    <span class="text-zinc-500">· {{ $action->city->country?->country }}</span>
                                @endif
                            </p>
                            @if ($action->description)
                                <p class="mt-1.5 max-w-xs text-xs leading-5 text-zinc-400">{{ $action->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2">
                                <div class="flex flex-col items-start gap-1">
                                    @forelse ($row['inputs'] as $input)
                                        <x-manual.item :item="$input['item']" :key="$input['key']" :quantity="$input['quantity']" />
                                    @empty
                                        <span class="text-xs text-zinc-500">Nothing</span>
                                    @endforelse
                                </div>
                                <flux:icon.arrow-right variant="micro" class="shrink-0 text-zinc-500" />
                                <div class="flex flex-col items-start gap-1">
                                    @if ($row['output'])
                                        <x-manual.item :item="$row['output']['item']" :key="$row['output']['key']" :quantity="$row['output']['quantity']" />
                                    @endif
                                    @if ($row['health'] > 0)
                                        <span class="rounded-sm border border-emerald-500/40 px-2 py-0.5 text-xs text-emerald-300">+{{ $row['health'] }} health</span>
                                    @endif
                                    @if (! $row['output'] && $row['health'] === 0)
                                        <span class="text-xs text-zinc-500">XP only</span>
                                    @endif
                                </div>
                            </div>
                            @foreach ($row['enemies'] as $enemy)
                                <p class="mt-2 flex items-center gap-1 text-xs text-orange-300"><flux:icon.fire variant="micro" /> Fight: {{ $enemy->name }} · {{ $enemy->health }} HP · hits {{ $enemy->min_damage }}–{{ $enemy->max_damage }}</p>
                            @endforeach
                        </td>
                        <td class="px-4 py-4 text-right font-mono tabular-nums text-ez-ink">{{ $row['xp'] }}</td>
                        <td class="px-4 py-4 text-right text-ez-ink">
                            <x-manual.duration :seconds="max(BuildManualCatalogue::MINIMUM_ACTION_SECONDS, $action->base_duration_seconds)" />
                            <p class="mt-1 text-[11px] whitespace-nowrap text-zinc-500">H+{{ rtrim(rtrim(number_format($action->hunger_cost, 1), '0'), '.') }} · F+{{ rtrim(rtrim(number_format($action->fatigue_cost, 1), '0'), '.') }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <x-manual.risk :level="$action->risk_level" />
                            @if ($action->hazard_chance_pct > 0)
                                <p class="mt-1.5 text-[11px] whitespace-nowrap text-red-300">{{ $action->hazard_chance_pct }}% · −{{ $action->hazard_damage }} HP</p>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Phone: one card per action --}}
    <ul class="grid gap-3 sm:grid-cols-2 lg:hidden">
        @foreach ($rows as $row)
            @php($action = $row['action'])
            <li id="action-m-{{ $action->action_key }}" @class(['ez-card p-4', 'ez-heat' => $row['enemies']->isNotEmpty() || $action->hazard_chance_pct > 0])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-ez-ink">{{ $action->label }}</p>
                        <p class="mt-0.5 text-xs text-ez-muted">
                            @if ($showSkill){{ $action->skill?->display_name }}@endif
                            @if ($showSkill && $showLocation) · @endif
                            @if ($showLocation)
                                <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($action->city)) }}" class="text-ez-signal" wire:navigate>{{ $action->city->city }}</a>
                            @endif
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="ez-label !text-[9px]">Level</p>
                        <p class="mt-0.5 font-mono text-xl font-semibold text-ez-signal">{{ $action->min_level }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    @forelse ($row['inputs'] as $input)
                        <x-manual.item :item="$input['item']" :key="$input['key']" :quantity="$input['quantity']" />
                    @empty
                        <span class="text-xs text-zinc-500">No items needed</span>
                    @endforelse
                    <flux:icon.arrow-right variant="micro" class="text-zinc-500" />
                    @if ($row['output'])
                        <x-manual.item :item="$row['output']['item']" :key="$row['output']['key']" :quantity="$row['output']['quantity']" />
                    @endif
                    @if ($row['health'] > 0)
                        <span class="rounded-sm border border-emerald-500/40 px-2 py-0.5 text-xs text-emerald-300">+{{ $row['health'] }} health</span>
                    @endif
                </div>
                @foreach ($row['enemies'] as $enemy)
                    <p class="mt-2 text-xs text-orange-300">Fight: {{ $enemy->name }} · {{ $enemy->health }} HP</p>
                @endforeach
                <dl class="mt-3 grid grid-cols-4 gap-2 border-t border-zinc-800 pt-3 text-xs">
                    <div><dt class="ez-label !text-[9px]">XP</dt><dd class="mt-1 font-mono text-ez-ink">{{ $row['xp'] }}</dd></div>
                    <div><dt class="ez-label !text-[9px]">Time</dt><dd class="mt-1 text-ez-ink"><x-manual.duration :seconds="max(BuildManualCatalogue::MINIMUM_ACTION_SECONDS, $action->base_duration_seconds)" /></dd></div>
                    <div><dt class="ez-label !text-[9px]">Cost</dt><dd class="mt-1 font-mono text-ez-ink">H{{ (float) $action->hunger_cost }} F{{ (float) $action->fatigue_cost }}</dd></div>
                    <div><dt class="ez-label !text-[9px]">Risk</dt><dd class="mt-1"><x-manual.risk :level="$action->risk_level" /></dd></div>
                </dl>
            </li>
        @endforeach
    </ul>
@endif
