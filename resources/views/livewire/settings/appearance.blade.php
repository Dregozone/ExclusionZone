<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-settings.layout :heading="__('Appearance')" :subheading="__('The Zone is always dark. Tune the effects and contrast to suit your eyes.')">
        <div
            x-data="{
                reduceEffects: document.documentElement.classList.contains('ez-reduce-effects'),
                highContrast: document.documentElement.classList.contains('ez-high-contrast'),
                save(key, className, enabled) {
                    document.documentElement.classList.toggle(className, enabled);

                    try {
                        window.localStorage.setItem(key, enabled ? '1' : '0');
                    } catch (error) {
                        // Storage blocked: the preference applies for this visit only.
                    }
                },
            }"
            x-init="
                $watch('reduceEffects', (enabled) => save('ez.reduce-effects', 'ez-reduce-effects', enabled));
                $watch('highContrast', (enabled) => save('ez.high-contrast', 'ez-high-contrast', enabled));
            "
            class="grid gap-3"
        >
            <label class="ez-card flex cursor-pointer items-start justify-between gap-4 p-4" for="reduce-effects">
                <span class="grid gap-1">
                    <span class="font-medium text-ez-ink">{{ __('Reduce effects') }}</span>
                    <span class="text-sm text-ez-muted">{{ __('Turns off film grain, scanlines, the Geiger needle and other motion. Also follows your device’s reduced-motion setting.') }}</span>
                </span>
                <input id="reduce-effects" type="checkbox" x-model="reduceEffects" class="mt-1 size-5 shrink-0 accent-ez-signal" data-test="reduce-effects-toggle">
            </label>

            <label class="ez-card flex cursor-pointer items-start justify-between gap-4 p-4" for="high-contrast">
                <span class="grid gap-1">
                    <span class="font-medium text-ez-ink">{{ __('High contrast') }}</span>
                    <span class="text-sm text-ez-muted">{{ __('Brighter secondary text and borders, and solid panels instead of see-through ones.') }}</span>
                </span>
                <input id="high-contrast" type="checkbox" x-model="highContrast" class="mt-1 size-5 shrink-0 accent-ez-signal" data-test="high-contrast-toggle">
            </label>

            <p class="ez-label mt-2">{{ __('Saved on this device') }}</p>
        </div>
    </x-settings.layout>
</section>
