<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('City Menu') }}
                </flux:navbar.item>
                <flux:navbar.item icon="sparkles" :href="route('landing')" :current="request()->routeIs('landing') || request()->routeIs('home')" wire:navigate>
                    {{ __('Landing') }}
                </flux:navbar.item>
                <flux:navbar.item icon="map" :href="route('world-map')" :current="request()->routeIs('world-map')" wire:navigate>
                    {{ __('World Map') }}
                </flux:navbar.item>
                <flux:navbar.item icon="map-pin" :href="route('admin.move-player')" :current="request()->routeIs('admin.move-player')" wire:navigate>
                    {{ __('Move Player') }}
                </flux:navbar.item>
                <flux:navbar.item icon="shield-check" :href="route('admin.change-user-role')" :current="request()->routeIs('admin.change-user-role')" wire:navigate>
                    {{ __('Change User Role') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Settings')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="cog"
                        :href="route('profile.edit')"
                        :label="__('Settings')"
                        wire:navigate
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('City Menu')  }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="sparkles" :href="route('landing')" :current="request()->routeIs('landing') || request()->routeIs('home')" wire:navigate>
                        {{ __('Landing') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map" :href="route('world-map')" :current="request()->routeIs('world-map')" wire:navigate>
                        {{ __('World Map') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Admin')">
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
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
