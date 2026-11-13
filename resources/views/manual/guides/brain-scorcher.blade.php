@php
    $outcomeLabels = [
        'jackpot' => ['Scorched jackpot', 'text-orange-300'],
        'upgrade' => ['One rarity up', 'text-emerald-300'],
        'same' => ['Same item back', 'text-ez-ink'],
        'downgrade' => ['One rarity down', 'text-amber-300'],
        'nothing' => ['Burned for nothing', 'text-red-300'],
    ];
@endphp

<x-manual.layout
    :title="$guide['title']"
    eyebrow="Guide 08 · Pripyat"
    lede="Deep in Pripyat, a humming Soviet machine will burn whatever you feed it, and sometimes give back something better."
    :image="$guide['image']"
    :breadcrumbs="['Guides' => route('manual.guide', 'getting-started'), $guide['title'] => null]"
    :toc="['how' => 'How it works', 'odds' => 'The odds', 'scorched' => 'Scorched items']"
>
    <x-manual.section id="how" title="How it works">
        <ol class="grid gap-3 md:grid-cols-3">
            @foreach ([
                ['Go to Pripyat', 'The Brain Scorcher is only found in Pripyat. It is irradiated, so bring food and medical items.'],
                ['Make an offering', 'Choose one item and how many copies to burn. The burn takes '.$burnMinutes.' minutes, then press Complete work.'],
                ['See what comes back', 'Your whole offering is used up. You get back at most one item: better, the same, worse, or nothing.'],
            ] as [$name, $copy])
                <li class="ez-card p-5">
                    <span class="ez-numeral text-5xl text-orange-300">{{ $loop->iteration }}</span>
                    <p class="mt-2 font-semibold text-ez-ink">{{ $name }}</p>
                    <p class="mt-1 text-sm leading-6 text-ez-muted">{{ $copy }}</p>
                </li>
            @endforeach
        </ol>
        <x-manual.callout type="warn" class="mt-4">Rarer items and bigger offerings run hotter: better odds of an upgrade or jackpot. Offering more than {{ $maxEffectiveQuantity }} copies stops helping.</x-manual.callout>
    </x-manual.section>

    <x-manual.section id="odds" title="The odds" intro="Percent chance of each outcome by the rarity you offer and how many copies.">
        <div class="ez-panel overflow-x-auto">
            <table class="w-full min-w-[40rem] text-sm">
                <thead>
                    <tr class="border-b border-zinc-700 text-left">
                        <th scope="col" class="ez-label px-4 py-3 font-normal">Offering</th>
                        @foreach ($outcomeLabels as [$label, $tone])
                            <th scope="col" class="ez-label px-3 py-3 text-right font-normal {{ $tone }}">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($odds as $entry)
                        @foreach ($entry['by_quantity'] as $quantity => $chances)
                            <tr @class(['border-zinc-800', 'border-t border-zinc-700' => $loop->first])>
                                <td class="px-4 py-2">
                                    @if ($loop->first)<span class="font-medium text-ez-ink">{{ $entry['rarity']->label() }}</span>@endif
                                    <span class="font-mono text-xs text-ez-muted">× {{ $quantity }}</span>
                                </td>
                                @foreach (array_keys($outcomeLabels) as $outcome)
                                    <td class="px-3 py-2 text-right font-mono tabular-nums {{ $chances[$outcome] > 0 ? $outcomeLabels[$outcome][1] : 'text-zinc-700' }}">{{ $chances[$outcome] }}%</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-manual.section>

    <x-manual.section id="scorched" title="Scorched items" intro="The rarest things in the Zone. No city action drops them; only the Brain Scorcher’s jackpot does.">
        <ul class="grid gap-3 sm:grid-cols-2">
            @foreach ($scorchedItems as $item)
                <li class="ez-card ez-heat p-4">
                    <x-manual.item :item="$item" size="md" />
                    <p class="mt-2 text-sm leading-6 text-ez-muted">{{ $item->description }}</p>
                </li>
            @endforeach
        </ul>
    </x-manual.section>

    <x-manual.pager :previous="$previous" :next="$next" />
</x-manual.layout>
