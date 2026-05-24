<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.empty')] class extends Component
{
    //
};
?>

<div>
    <div class="min-h-screen bg-[radial-gradient(circle_at_top,rgba(74,222,128,0.14),transparent_35%),linear-gradient(180deg,#09090b_0%,#111827_45%,#050816_100%)] text-zinc-100">
        <section class="border-b border-white/10">
            <div class="mx-auto flex max-w-6xl flex-col gap-10 px-6 py-8 lg:px-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-emerald-300/80">Text-based survival MMO</p>
                        <h1 class="mt-2 text-4xl font-semibold tracking-tight sm:text-6xl">Exclusion Zone</h1>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-full border border-emerald-400/40 bg-emerald-400/10 px-5 py-2.5 text-sm font-medium text-emerald-200 transition hover:bg-emerald-400/20">
                                Enter your city menu
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center rounded-full border border-emerald-400/40 bg-emerald-400/10 px-5 py-2.5 text-sm font-medium text-emerald-200 transition hover:bg-emerald-400/20">
                                Register
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center rounded-full bg-emerald-400 px-5 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300">
                                Login
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="grid gap-8 lg:grid-cols-[1.3fr_0.7fr]">
                    <div class="space-y-6">
                        <p class="max-w-3xl text-lg leading-8 text-zinc-300 sm:text-xl">
                            Build a survivor in a fractured world of irradiated ruins, fortified hubs, black markets,
                            dangerous hunts, and long-distance scavenging runs. One click drives one move or one action,
                            making the MVP fast to learn and easy to demo.
                        </p>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-sm text-zinc-400">Core loop</p>
                                <p class="mt-2 text-lg font-medium text-white">Travel, scavenge, level, repeat</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-sm text-zinc-400">World</p>
                                <p class="mt-2 text-lg font-medium text-white">12 cities across 6 countries</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-sm text-zinc-400">Premium</p>
                                <p class="mt-2 text-lg font-medium text-white">Cosmetic only, never pay-to-win</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-zinc-950/70 p-6 shadow-2xl shadow-emerald-950/30">
                        <p class="text-sm uppercase tracking-[0.3em] text-amber-300/80">Field report</p>
                        <div class="mt-4 space-y-4 text-sm leading-7 text-zinc-300">
                            <p>“Kyiv sectors are holding. Pripyat is still glowing. Detroit factories are full of broken steel and armed scavengers.”</p>
                            <p>“Tokyo tech ruins keep producing parts. Manaus pays well if you can survive the jungle edge.”</p>
                            <p>“Premium backers get style, not strength. Nobody buys victory in the Zone.”</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-6 py-16 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <h2 class="text-xl font-semibold text-white">Survival-driven cities</h2>
                    <p class="mt-3 text-sm leading-7 text-zinc-300">
                        Pripyat, Kyiv, Warsaw, Gdansk, Detroit, Seattle, Tokyo, Sapporo, Rio de Janeiro, Manaus,
                        Johannesburg, and Cape Town all ship with themed actions and travel links.
                    </p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <h2 class="text-xl font-semibold text-white">Action-per-click gameplay</h2>
                    <p class="mt-3 text-sm leading-7 text-zinc-300">
                        Every action grants visible experience and salvage. Every move changes the city menu,
                        allowing the MVP to feel like a living route-planning scavenger run.
                    </p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <h2 class="text-xl font-semibold text-white">Ready for expansion</h2>
                    <p class="mt-3 text-sm leading-7 text-zinc-300">
                        Chat, trade, combat, moderation, premium cosmetics, and admin role control are wired in as
                        authenticated hooks so the concept can expand without replacing the core loop.
                    </p>
                </div>
            </div>
        </section>

        <section class="border-y border-white/10 bg-black/20">
            <div class="mx-auto max-w-6xl px-6 py-16 lg:px-8">
                <div class="flex flex-col gap-4 text-center">
                    <p class="text-sm uppercase tracking-[0.3em] text-zinc-400">What the MVP includes</p>
                    <h2 class="text-3xl font-semibold text-white sm:text-4xl">A working game loop you can play right now</h2>
                </div>

                <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        'Register and login straight into your current city menu.',
                        'Move between connected cities and refresh local actions instantly.',
                        'Gain skill XP, level up, and watch inventory fill with scavenged loot.',
                        'Equip cosmetic premium loadouts without any gameplay advantage.',
                    ] as $feature)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-sm leading-7 text-zinc-300">
                            {{ $feature }}
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <footer class="mx-auto flex max-w-6xl flex-col gap-3 px-6 py-8 text-sm text-zinc-500 lg:px-8">
            <p>&copy; {{ date('Y') }} Exclusion Zone.</p>
            <p>The MVP is a fictional post-nuclear game concept focused on survival, atmosphere, and fair progression.</p>
        </footer>
    </div>
</div>
