@props([
    'id',
    'title',
    'eyebrow' => null,
    'intro' => null,
])

<section id="{{ $id }}" {{ $attributes->class(['scroll-mt-24']) }} aria-labelledby="{{ $id }}-heading">
    @if ($eyebrow)
        <p class="ez-label">{{ $eyebrow }}</p>
    @endif
    <h2 id="{{ $id }}-heading" class="ez-display mt-1 text-4xl text-ez-ink">
        <a href="#{{ $id }}" class="hover:text-ez-signal">{{ $title }}</a>
    </h2>
    @if ($intro)
        <p class="mt-3 max-w-3xl leading-7 text-ez-muted">{{ $intro }}</p>
    @endif
    <div class="mt-6">{{ $slot }}</div>
</section>
