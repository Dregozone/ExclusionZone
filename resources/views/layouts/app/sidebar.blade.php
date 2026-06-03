<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Zone')" class="grid">
                        @auth
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('City Menu') }}
                        </flux:sidebar.item>
                        @endauth
                        <flux:sidebar.item icon="sparkles" :href="route('landing')" :current="request()->routeIs('landing') || request()->routeIs('home')" wire:navigate>
                            {{ __('Landing') }}
                        </flux:sidebar.item>
                        
                        <flux:sidebar.item icon="map" :href="route('world-map')" :current="request()->routeIs('world-map')" wire:navigate>
                            {{ __('World Map') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item icon="chart-bar" :href="route('high-scores')" :current="request()->routeIs('high-scores')" wire:navigate>
                            {{ __('High Scores') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    @if (auth()->user()?->isAdmin())
                    <flux:sidebar.group :heading="__('Admin')" class="grid">
                        <flux:sidebar.item icon="map-pin" :href="route('admin.move-player')" :current="request()->routeIs('admin.move-player')" wire:navigate>
                            {{ __('Move Player') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="shield-check" :href="route('admin.change-user-role')" :current="request()->routeIs('admin.change-user-role')" wire:navigate>
                            {{ __('Change User Role') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="building-office-2" :href="route('admin.locations')" :current="request()->routeIs('admin.locations')" wire:navigate>
                            {{ __('Locations') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="globe-alt" :href="route('admin.countries')" :current="request()->routeIs('admin.countries')" wire:navigate>
                            {{ __('Countries') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="bolt" :href="route('admin.city-actions')" :current="request()->routeIs('admin.city-actions')" wire:navigate>
                            {{ __('City Actions') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="archive-box" :href="route('admin.items')" :current="request()->routeIs('admin.items')" wire:navigate>
                            {{ __('Items') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="academic-cap" :href="route('admin.skills')" :current="request()->routeIs('admin.skills')" wire:navigate>
                            {{ __('Skills') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.quests')" :current="request()->routeIs('admin.quests')" wire:navigate>
                            {{ __('Quests') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                    @endif
                </flux:sidebar.nav>

                <flux:spacer />

                <flux:sidebar.nav>
                    @auth
                    <flux:sidebar.item icon="cog" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                    @else
                    <flux:sidebar.item icon="arrow-right-end-on-rectangle" :href="route('login')" wire:navigate>
                        {{ __('Log in') }}
                    </flux:sidebar.item>
                    @endauth
                </flux:sidebar.nav>

            @auth
            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            @endauth
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @auth
            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
            @else
            <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                {{ __('Log in') }}
            </a>
            @endauth
        </flux:header>

        {{ $slot }}

        @if (session()->has('toast'))
            @php($toast = session('toast'))

            <div
                x-data="{ toast: @js($toast) }"
                x-init="$nextTick(() => $flux.toast(toast))"
                class="hidden"
                aria-hidden="true"
            ></div>
        @endif

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
