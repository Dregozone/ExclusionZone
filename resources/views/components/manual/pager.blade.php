@props(['previous' => null, 'next' => null])

<nav aria-label="Guide pages" class="grid gap-3 sm:grid-cols-2">
    @if ($previous)
        <a href="{{ route('manual.guide', $previous['slug']) }}" class="ez-card group p-5 transition hover:border-ez-signal/60" wire:navigate>
            <p class="ez-label">← Previous</p>
            <p class="mt-2 text-lg font-semibold text-ez-ink group-hover:text-ez-signal">{{ $previous['title'] }}</p>
            <p class="mt-1 text-sm text-ez-muted">{{ $previous['summary'] }}</p>
        </a>
    @else
        <a href="{{ route('manual.index') }}" class="ez-card group p-5 transition hover:border-ez-signal/60" wire:navigate>
            <p class="ez-label">← Back</p>
            <p class="mt-2 text-lg font-semibold text-ez-ink group-hover:text-ez-signal">Manual overview</p>
            <p class="mt-1 text-sm text-ez-muted">Every guide, skill and place in one place.</p>
        </a>
    @endif

    @if ($next)
        <a href="{{ route('manual.guide', $next['slug']) }}" class="ez-card group p-5 text-right transition hover:border-ez-signal/60" wire:navigate>
            <p class="ez-label">Next →</p>
            <p class="mt-2 text-lg font-semibold text-ez-ink group-hover:text-ez-signal">{{ $next['title'] }}</p>
            <p class="mt-1 text-sm text-ez-muted">{{ $next['summary'] }}</p>
        </a>
    @else
        <a href="{{ route('manual.faq') }}" class="ez-card group p-5 text-right transition hover:border-ez-signal/60" wire:navigate>
            <p class="ez-label">Next →</p>
            <p class="mt-2 text-lg font-semibold text-ez-ink group-hover:text-ez-signal">FAQ</p>
            <p class="mt-1 text-sm text-ez-muted">Quick answers to common questions.</p>
        </a>
    @endif
</nav>
