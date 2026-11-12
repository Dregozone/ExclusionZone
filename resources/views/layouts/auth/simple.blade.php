<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="ez-world ez-grain min-h-screen text-ez-ink antialiased">
        <div class="grid min-h-svh lg:grid-cols-[1.1fr_0.9fr]">
            {{-- Atlas side: the place you are about to enter --}}
            <div class="relative hidden flex-col justify-between overflow-hidden border-e border-zinc-700 p-10 lg:flex" aria-hidden="true">
                <a href="{{ route('home') }}" class="flex items-center gap-3 text-ez-signal" wire:navigate tabindex="-1">
                    <x-app-logo-icon class="size-9" />
                    <span class="ez-display text-2xl text-ez-ink">Exclusion Zone</span>
                </a>

                <div class="space-y-6">
                    <p class="ez-label text-ez-signal">51.3890° N · 30.0990° E · Zone ring 3</p>
                    <p class="ez-display text-[clamp(4rem,9vw,8.5rem)] text-ez-ink">Pripyat</p>
                    <p class="max-w-md text-sm leading-6 text-ez-muted">Twelve cities, six countries, one long walk between them. Scavenge, trade on the radio, pay your way past the checkpoints, and try not to glow.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-sm border border-amber-400/60 px-2.5 py-1 font-mono text-[11px] text-amber-400">▲ Ash fall · +2 mSv/hr</span>
                    <span class="rounded-sm border border-zinc-700 px-2.5 py-1 font-mono text-[11px] text-ez-muted">12 survivors on frequency</span>
                </div>
            </div>

            {{-- Form side --}}
            <div class="flex flex-col items-center justify-center gap-6 px-4 py-10 sm:px-10">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 text-ez-signal lg:hidden" wire:navigate>
                    <x-app-logo-icon class="size-11" />
                    <span class="ez-display text-2xl text-ez-ink">Exclusion Zone</span>
                </a>

                <div class="ez-panel w-full max-w-sm p-6 sm:p-8">
                    <div class="flex flex-col gap-6">
                        {{ $slot }}
                    </div>
                </div>

                <p class="ez-label">Channel 7 · signal secured</p>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
