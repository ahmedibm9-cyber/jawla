<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ app()->getLocale() === 'ar' ? 'تحصيل' : 'Collect Payment' }}</span>
                <span wire:loading>{{ app()->getLocale() === 'ar' ? 'جاري…' : 'Saving…' }}</span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
