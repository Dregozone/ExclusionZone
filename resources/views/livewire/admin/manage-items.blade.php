<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Items') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Create, edit, and soft-delete loot items.') }}</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ __('New Item') }}</flux:button>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:heading size="lg">{{ $editingId ? __('Edit Item') : __('Create Item') }}</flux:heading>
            </div>

            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Item Key') }}</flux:label>
                        <flux:input wire:model="key" placeholder="{{ __('e.g. scrap_metal') }}" />
                        <flux:error name="key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Name') }}</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('e.g. Scrap Metal') }}" />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="description" placeholder="{{ __('What this item is and how it might be used…') }}" rows="3" />
                    <flux:error name="description" />
                </flux:field>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">{{ $editingId ? __('Update Item') : __('Create Item') }}</flux:button>
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <flux:checkbox wire:model.live="showTrashed" id="showTrashed" />
        <label for="showTrashed" class="cursor-pointer text-sm text-zinc-600 dark:text-zinc-400">{{ __('Show deleted items') }}</label>
    </div>

    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-md:hidden">{{ __('Key') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->items as $item)
                    <tr wire:key="item-{{ $item->id }}" class="{{ $item->deleted_at ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $item->name }}
                            @if ($item->deleted_at)
                                <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Deleted') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-500 dark:text-zinc-400 max-md:hidden">{{ $item->key }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-lg:hidden truncate max-w-xs">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($item->deleted_at)
                                <flux:button size="sm" wire:click="restore({{ $item->id }})" wire:confirm="{{ __('Restore this item?') }}" variant="ghost" icon="arrow-path">{{ __('Restore') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="edit({{ $item->id }})" variant="ghost" icon="pencil">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Soft-delete this item?') }}" variant="ghost" icon="trash">{{ __('Delete') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No items found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
