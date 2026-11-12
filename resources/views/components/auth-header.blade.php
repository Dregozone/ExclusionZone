@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <h1 class="ez-display text-4xl text-ez-ink">{{ $title }}</h1>
    <p class="text-sm text-ez-muted">{{ $description }}</p>
</div>
