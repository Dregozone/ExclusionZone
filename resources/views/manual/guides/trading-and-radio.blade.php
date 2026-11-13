@php
    use App\Actions\Game\BuildManualCatalogue;
@endphp

<x-manual.layout
    :title="$guide['title']"
    eyebrow="Guide 07 · Other survivors"
    lede="You are not alone out here. Swap goods face to face at a trade post, and keep in touch with everyone over the radio."
    :image="$guide['image']"
    :breadcrumbs="['Guides' => route('manual.guide', 'getting-started'), $guide['title'] => null]"
    :toc="['posts' => 'Trade posts', 'trade' => 'Making a trade', 'radio' => 'The radio', 'rules' => 'Fair play']"
>
    <x-manual.section id="posts" title="Trade posts" intro="Trading only works when both survivors stand in the same city, and that city has a trade post.">
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($tradePosts as $city)
                <li>
                    <a href="{{ route('manual.location', BuildManualCatalogue::citySlug($city)) }}" class="ez-card ez-pin group block p-4 pt-5 transition hover:border-ez-signal/60" wire:navigate>
                        <p class="ez-display text-3xl text-ez-ink group-hover:text-ez-signal">{{ $city->city }}</p>
                        <p class="text-sm text-ez-muted">{{ $city->country?->country }} · {{ $city->country?->continent }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
        <x-manual.callout class="mt-4">Trade posts double as the place you wake up after dying, so it pays to know where the nearest one is on your continent.</x-manual.callout>
    </x-manual.section>

    <x-manual.section id="trade" title="Making a trade">
        <ol class="grid gap-3 md:grid-cols-4">
            @foreach ([
                ['Request', 'Open the Trade board in your PDA and ask a survivor at the same post to trade.'],
                ['Open the table', 'When they accept, a trade window opens for both of you.'],
                ['Offer items', 'Put items on the table or take them off. Any change cancels both sides’ agreement, so nobody is surprised.'],
                ['Both commit', 'When you have both committed to what is on the table, the items swap packs at once.'],
            ] as [$name, $copy])
                <li class="ez-card p-4">
                    <span class="ez-numeral text-5xl text-ez-signal">{{ $loop->iteration }}</span>
                    <p class="mt-2 font-semibold text-ez-ink">{{ $name }}</p>
                    <p class="mt-1 text-sm leading-6 text-ez-muted">{{ $copy }}</p>
                </li>
            @endforeach
        </ol>
        <p class="mt-4 text-sm text-ez-muted">One open trade at a time. Quest items can’t be traded.</p>
    </x-manual.section>

    <x-manual.section id="radio" title="The radio">
        <div class="ez-panel ez-scanlines grid gap-4 p-5 md:grid-cols-[1fr_1fr]">
            <div class="space-y-2 text-sm">
                <p class="ez-phosphor">&gt; [Kyiv] scav_77: anyone got spare clean water? paying in scrap</p>
                <p class="ez-phosphor">&gt; [Warsaw] Ola: trade post open, come find me</p>
                <p class="ez-phosphor ez-cursor">&gt; [Pripyat] ghost: don't feed the scorcher epics. trust me.</p>
            </div>
            <div class="text-sm leading-6 text-ez-ink">
                <p>The <strong>Radio</strong> tab in your PDA is one channel shared by every survivor in the Zone. Messages arrive live.</p>
                <p class="mt-2 text-ez-muted">Use it to find trade partners, ask for help or warn others. Older messages are in <a href="{{ route('radio.history') }}" class="text-ez-signal hover:underline">radio history</a> (sign in needed).</p>
            </div>
        </div>
    </x-manual.section>

    <x-manual.section id="rules" title="Fair play">
        <div class="grid gap-3 md:grid-cols-3">
            <x-manual.callout title="Be decent">Moderators can mute survivors on the radio for a while. Spam and abuse get you muted.</x-manual.callout>
            <x-manual.callout title="Cosmetics only">Premium backers get outfits, themes and flair. Nobody can buy strength, skills or items.</x-manual.callout>
            <x-manual.callout type="warn" title="Check before you commit">Read the trade table before committing. Once both sides commit, the swap is final.</x-manual.callout>
        </div>
    </x-manual.section>

    <x-manual.pager :previous="$previous" :next="$next" />
</x-manual.layout>
