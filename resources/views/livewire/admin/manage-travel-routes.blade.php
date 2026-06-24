<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Travel Routes') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Set the base travel duration (in seconds) for each directional city connection. Minimum is always 10 s.') }}</flux:text>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('From') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('To') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Duration') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->routes as $route)
                    @php $isEditing = $editingCityId === $route->city_id && $editingNeighborId === $route->neighbor_city_id; @endphp
                    <tr wire:key="route-{{ $route->city_id }}-{{ $route->neighbor_city_id }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $route->from_city_name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $route->to_city_name }}</td>
                        <td class="px-4 py-3">
                            @if ($isEditing)
                                <form wire:submit="save" class="flex items-center gap-2">
                                    <flux:input
                                        type="number"
                                        wire:model="editingDuration"
                                        min="10"
                                        class="w-28"
                                    />
                                    <span class="text-zinc-500 dark:text-zinc-400">s</span>
                                    <flux:error name="editingDuration" />
                                </form>
                            @else
                                <span class="text-zinc-600 dark:text-zinc-400">{{ $route->duration_seconds }}s</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($isEditing)
                                <flux:button size="sm" wire:click="save" variant="primary" icon="check">{{ __('Save') }}</flux:button>
                                <flux:button size="sm" wire:click="cancel" variant="ghost" icon="x-mark">{{ __('Cancel') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="edit({{ $route->city_id }}, {{ $route->neighbor_city_id }}, {{ $route->duration_seconds }})" variant="ghost" icon="pencil">{{ __('Edit') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No travel routes found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
