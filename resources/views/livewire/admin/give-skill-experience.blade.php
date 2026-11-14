<section class="w-full">
    <div class="mb-6">
        <p class="ez-label !text-amber-400">{{ __('Admin console') }}</p>
            <flux:heading size="xl" class="ez-display !text-4xl !font-normal sm:!text-5xl">{{ __('Give Skill XP') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Select a player and a skill, enter an amount of experience, then confirm. Levels are gained as the experience allows.') }}</flux:text>
    </div>

    <div class="ez-panel p-6">
        <form wire:submit="giveExperience" class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Player') }}</flux:label>
                <flux:select wire:model="selectedUserId" placeholder="{{ __('Select a player…') }}" searchable>
                    <flux:select.option value="">{{ __('Select a player…') }}</flux:select.option>
                    @foreach ($this->users as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedUserId" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Skill') }}</flux:label>
                <flux:select wire:model="selectedSkillId" placeholder="{{ __('Select a skill…') }}" searchable>
                    <flux:select.option value="">{{ __('Select a skill…') }}</flux:select.option>
                    @foreach ($this->skills as $skill)
                        <flux:select.option value="{{ $skill->id }}">{{ $skill->display_name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedSkillId" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Experience') }}</flux:label>
                <flux:input type="number" wire:model="experience" min="1" max="1000000" step="1" />
                <flux:error name="experience" />
            </flux:field>

            <flux:button type="submit" variant="primary" icon="arrow-trending-up">
                {{ __('Give Experience') }}
            </flux:button>
        </form>
    </div>
</section>
