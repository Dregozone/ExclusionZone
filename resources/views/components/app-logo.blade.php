@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Exclusion Zone" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-full text-ez-signal">
            <x-app-logo-icon class="size-7" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Exclusion Zone" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-full text-ez-signal">
            <x-app-logo-icon class="size-7" />
        </x-slot>
    </flux:brand>
@endif
