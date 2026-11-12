@props([
    'message',
    'isOwn' => false,
    'compact' => false,
])

{{-- Radio traffic reads like a phosphor terminal log; your own transmissions sit right in signal teal. --}}
<div {{ $attributes->class([
    'flex items-end gap-2',
    'flex-row-reverse' => $isOwn,
]) }}>
    <flux:avatar
        :name="$message->user?->name ?? 'Unknown'"
        color="auto"
        size="xs"
        circle
        class="shrink-0"
    />

    <div @class([
        'max-w-[85%] min-w-0 border',
        'px-2.5 py-1' => $compact,
        'px-3 py-1.5' => ! $compact,
        'rounded-sm border-emerald-400/40 bg-emerald-950/60' => $isOwn,
        'rounded-sm border-zinc-700 border-l-2 border-l-ez-phosphor/60 bg-zinc-950/80' => ! $isOwn,
    ])>
        <p @class([
            'flex items-baseline gap-2 font-mono text-[10px] uppercase tracking-wider',
            'justify-end text-emerald-300' => $isOwn,
            'text-ez-phosphor' => ! $isOwn,
        ])>
            <span class="truncate">{{ $message->user?->name ?? 'Unknown' }}</span>
            <time class="shrink-0 text-zinc-500 normal-case tracking-normal" datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format($compact ? 'H:i' : 'd M, H:i') }}</time>
        </p>
        <p @class([
            'whitespace-pre-line break-words',
            'text-ez-ink' => $isOwn,
            'ez-phosphor' => ! $isOwn,
            'text-xs' => $compact,
            'text-sm' => ! $compact,
        ])>{{ $message->body }}</p>
    </div>
</div>
