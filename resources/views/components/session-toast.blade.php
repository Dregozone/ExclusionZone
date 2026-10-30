@props(['toast'])

<div
    x-data="{ toast: @js($toast) }"
    x-init="$nextTick(() => $flux.toast({ duration: 10000, ...toast }))"
    class="hidden"
    aria-hidden="true"
></div>
