@php
    use App\Actions\Game\BuildManualCatalogue;

    $rarityTone = match ($item->rarity->value) {
        'uncommon' => 'border-emerald-500/50 text-emerald-300',
        'rare' => 'border-sky-400/50 text-sky-300',
        'epic' => 'border-fuchsia-400/50 text-fuchsia-300',
        'scorched' => 'border-orange-500/60 text-orange-300',
        default => 'border-zinc-600 text-ez-ink',
    };
    $hasSource = $produced_by->isNotEmpty() || $dropped_by->isNotEmpty() || $trouble_loot->isNotEmpty() || $quest_rewards->isNotEmpty() || $item->rarity->isScorcherExclusive();
    $stats = array_filter([
        'Hunger' => $item->hunger_restored ? '−'.$item->hunger_restored : null,
        'Fatigue' => $item->fatigue_restored ? '−'.$item->fatigue_restored : null,
        'Health' => $item->health_restored ? '+'.$item->health_restored : null,
        'Attack' => $item->attack_bonus ? '+'.$item->attack_bonus : null,
        'Barter value' => (string) $item->barterValue(),
    ]);
@endphp

<x-manual.layout
    :title="$item->name"
    eyebrow="Item"
    :lede="$item->description"
    :breadcrumbs="['Items' => route('manual.items'), $item->name => null]"
    :toc="['get' => 'How to get it', 'use' => 'What it is for']"
>
    <x-slot:meta>
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-sm border px-2 py-0.5 font-mono text-[11px] tracking-wider uppercase {{ $rarityTone }}">{{ $item->rarity->label() }}</span>
            @if ($item->is_quest_item)<span class="rounded-sm border border-ez-paper/50 px-2 py-0.5 font-mono text-[11px] text-ez-paper uppercase">Quest item · can't be traded or lost</span>@endif
            @if ($item->isFood())<span class="rounded-sm border border-zinc-600 px-2 py-0.5 font-mono text-[11px] text-ez-ink uppercase">Food · Eat</span>@endif
            @if ($item->isMedical())<span class="rounded-sm border border-emerald-500/50 px-2 py-0.5 font-mono text-[11px] text-emerald-300 uppercase">Medical · Use</span>@endif
            @if ($item->isWeapon())<span class="rounded-sm border border-fuchsia-400/50 px-2 py-0.5 font-mono text-[11px] text-fuchsia-300 uppercase">Weapon · automatic</span>@endif
        </div>
        <dl class="mt-5 flex flex-wrap gap-x-8 gap-y-3">
            @foreach ($stats as $label => $value)
                <div><dt class="ez-label">{{ $label }}</dt><dd class="ez-numeral mt-1 text-4xl text-ez-ink">{{ $value }}</dd></div>
            @endforeach
        </dl>
    </x-slot:meta>

    <x-manual.section id="get" title="How to get it">
        @unless ($hasSource)
            <p class="ez-card p-5 text-sm text-ez-muted">No action, enemy or quest gives this right now. Look for it from other survivors at a trade post.</p>
        @endunless

        @if ($produced_by->isNotEmpty())
            <p class="ez-label mb-3">Made or gathered by</p>
            <x-manual.action-table :rows="$produced_by" :show-skill="true" />
        @endif

        @if ($dropped_by->isNotEmpty() || $trouble_loot->isNotEmpty())
            <p class="ez-label mt-8 mb-3">Dropped by</p>
            <ul class="grid gap-3 md:grid-cols-2">
                @foreach ($dropped_by as $drop)
                    <li class="ez-card ez-heat flex items-center justify-between gap-3 p-4">
                        <span>
                            <span class="block font-semibold text-ez-ink">{{ $drop['enemy']->name }}</span>
                            <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($drop['enemy']->city)) }}" class="text-xs text-ez-signal hover:underline" wire:navigate>{{ $drop['enemy']->city->city }}</a>
                            <span class="text-xs text-ez-muted">· {{ $drop['enemy']->skill?->display_name }}</span>
                        </span>
                        <span class="text-right font-mono text-sm"><span class="text-ez-ink">{{ $drop['quantity'] }}×</span> <span class="text-ez-muted">{{ $drop['chance_percent'] }}%</span></span>
                    </li>
                @endforeach
                @foreach ($trouble_loot as $type)
                    <li class="ez-card flex items-center justify-between gap-3 p-4">
                        <span>
                            <span class="block font-semibold text-ez-ink">{{ $type->name }}</span>
                            <a href="{{ route('manual.guide', 'travel') }}#types" class="text-xs text-ez-signal hover:underline" wire:navigate>Trouble on arrival · beat them</a>
                        </span>
                        <span class="font-mono text-sm text-ez-ink">{{ $type->loot_quantity }}×</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($quest_rewards->isNotEmpty())
            <p class="ez-label mt-8 mb-3">Quest reward</p>
            <ul class="flex flex-wrap gap-2">
                @foreach ($quest_rewards as $quest)
                    <li><a href="{{ route('manual.quests') }}#quest-{{ $quest->id }}" class="ez-card inline-block px-3 py-2 text-sm text-ez-ink hover:text-ez-signal" wire:navigate>{{ $quest->name }} <span class="font-mono text-xs text-ez-muted">× {{ $quest->reward_item_quantity }}</span></a></li>
                @endforeach
            </ul>
        @endif

        @if ($item->rarity->isScorcherExclusive())
            <x-manual.callout type="danger" class="mt-6" title="Brain Scorcher only">
                Scorched items only come out of the <a href="{{ route('manual.guide', 'brain-scorcher') }}" class="underline" wire:navigate>Brain Scorcher</a> in Pripyat, as a jackpot.
            </x-manual.callout>
        @endif
    </x-manual.section>

    <x-manual.section id="use" title="What it is for">
        <div class="space-y-6">
            @if ($used_by->isNotEmpty())
                <div>
                    <p class="ez-label mb-3">Used up by</p>
                    <x-manual.action-table :rows="$used_by" :show-skill="true" />
                </div>
            @endif

            <ul class="grid gap-3 md:grid-cols-2">
                @if ($item->isFood())
                    <li><x-manual.callout title="Eat it">Press Eat in your Rations panel or inventory, even while working. It eases hunger and fatigue and restores health as shown above.</x-manual.callout></li>
                @endif
                @if ($item->isMedical())
                    <li><x-manual.callout title="Use it">Press Use in the Medical panel to restore {{ $item->health_restored }} health at once.</x-manual.callout></li>
                @endif
                @if ($item->isWeapon())
                    <li><x-manual.callout title="Carry it">The best weapon in your pack adds its attack bonus to every blow in a fight. No need to equip it. <a href="{{ route('manual.guide', 'combat') }}" class="underline" wire:navigate>Fights guide</a></x-manual.callout></li>
                @endif
                @unless ($item->is_quest_item)
                    <li><x-manual.callout type="lore" title="Barter">Worth {{ $item->barterValue() }} toward buying your way past trouble, and tradeable with other survivors at a trade post.</x-manual.callout></li>
                    <li><x-manual.callout type="warn" title="Scorch it">Feed it to the Brain Scorcher in Pripyat for a chance at a {{ $item->rarity->higher()->label() }} item or a Scorched jackpot.</x-manual.callout></li>
                @else
                    <li><x-manual.callout title="Quest item">Needed for a quest step. It is spared when you die and can’t be traded or handed to trouble.</x-manual.callout></li>
                @endunless
            </ul>
        </div>
    </x-manual.section>
</x-manual.layout>
