@props([
    'message',
    'isOwn' => false,
    'compact' => false,
])

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
        'max-w-[80%] rounded-2xl border shadow-sm',
        'px-2.5 py-1' => $compact,
        'px-3 py-1.5' => ! $compact,
        'rounded-br-sm border-emerald-700/50 bg-emerald-900/40' => $isOwn,
        'rounded-bl-sm border-zinc-800 bg-zinc-900' => ! $isOwn,
    ])>
        <p @class([
            'truncate font-mono text-[10px] uppercase tracking-wide',
            'text-right text-emerald-300' => $isOwn,
            'text-emerald-500' => ! $isOwn,
        ])>{{ $message->user?->name ?? 'Unknown' }}</p>
        <p @class([
            'whitespace-pre-line break-words text-zinc-200',
            'text-xs' => $compact,
            'text-sm' => ! $compact,
        ])>{{ $message->body }}</p>
        <p class="mt-0.5 text-right font-mono text-[9px] text-zinc-500">
            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format($compact ? 'H:i' : 'd M, H:i') }}</time>
        </p>
    </div>
</div>
