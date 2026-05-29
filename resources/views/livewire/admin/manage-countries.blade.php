<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Manage Countries') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Create, edit, and soft-delete countries that house cities.') }}</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">{{ __('New Country') }}</flux:button>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-3xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4">
                <flux:heading size="lg">{{ $editingId ? __('Edit Country') : __('Create Country') }}</flux:heading>
            </div>

            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Continent') }}</flux:label>
                        <flux:input wire:model="continent" placeholder="{{ __('e.g. Europe') }}" />
                        <flux:error name="continent" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Country Name') }}</flux:label>
                        <flux:input wire:model="country" placeholder="{{ __('e.g. Ukraine') }}" />
                        <flux:error name="country" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Avg Temp (°C)') }}</flux:label>
                        <flux:input type="number" wire:model="avg_temp_c" min="-60" max="60" />
                        <flux:error name="avg_temp_c" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Rain Chance (%)') }}</flux:label>
                        <flux:input type="number" wire:model="rain_chance_pct" min="0" max="100" />
                        <flux:error name="rain_chance_pct" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Trouble Chance (%)') }}</flux:label>
                        <flux:input type="number" wire:model="trouble_chance_pct" min="0" max="100" />
                        <flux:error name="trouble_chance_pct" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Notes') }}</flux:label>
                    <flux:textarea wire:model="notes" placeholder="{{ __('Optional flavour text…') }}" rows="3" />
                    <flux:error name="notes" />
                </flux:field>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">{{ $editingId ? __('Update Country') : __('Create Country') }}</flux:button>
                    <flux:button wire:click="cancel" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 flex items-center gap-3">
        <flux:checkbox wire:model.live="showTrashed" id="showTrashed" />
        <label for="showTrashed" class="cursor-pointer text-sm text-zinc-600 dark:text-zinc-400">{{ __('Show deleted countries') }}</label>
    </div>

    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">{{ __('Country') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-md:hidden">{{ __('Continent') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-lg:hidden">{{ __('Avg °C') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 max-xl:hidden">{{ __('Notes') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->countries as $c)
                    <tr wire:key="country-{{ $c->id }}" class="{{ $c->deleted_at ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $c->country }}
                            @if ($c->deleted_at)
                                <span class="ml-2 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">{{ __('Deleted') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-md:hidden">{{ $c->continent }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-lg:hidden">{{ $c->avg_temp_c }}°</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 max-xl:hidden truncate max-w-xs">{{ $c->notes }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($c->deleted_at)
                                <flux:button size="sm" wire:click="restore({{ $c->id }})" wire:confirm="{{ __('Restore this country?') }}" variant="ghost" icon="arrow-path">{{ __('Restore') }}</flux:button>
                            @else
                                <flux:button size="sm" wire:click="edit({{ $c->id }})" variant="ghost" icon="pencil">{{ __('Edit') }}</flux:button>
                                <flux:button size="sm" wire:click="delete({{ $c->id }})" wire:confirm="{{ __('Soft-delete this country?') }}" variant="ghost" icon="trash">{{ __('Delete') }}</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('No countries found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
