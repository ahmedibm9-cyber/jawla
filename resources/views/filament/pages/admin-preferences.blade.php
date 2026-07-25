<x-filament-panels::page>
    <div class="max-w-xl">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            {{ l('رتّب أقسام القائمة الجانبية حسب تفضيلك. يُطبَّق الترتيب على حسابك فقط.', 'Reorder the sidebar sections to your preference. The order applies to your account only.') }}
        </p>

        <ul class="divide-y divide-gray-100 dark:divide-white/10 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 overflow-hidden">
            @foreach ($order as $index => $group)
                <li class="flex items-center justify-between gap-3 px-4 py-3" wire:key="navgroup-{{ $index }}-{{ $group }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-xs font-medium text-gray-400 tabular-nums w-5 text-center">{{ $index + 1 }}</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $group }}</span>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <x-filament::icon-button
                            icon="heroicon-o-chevron-up"
                            color="gray"
                            size="sm"
                            :label="l('تحريك لأعلى', 'Move up')"
                            wire:click="moveUp({{ $index }})"
                            :disabled="$loop->first"
                        />
                        <x-filament::icon-button
                            icon="heroicon-o-chevron-down"
                            color="gray"
                            size="sm"
                            :label="l('تحريك لأسفل', 'Move down')"
                            wire:click="moveDown({{ $index }})"
                            :disabled="$loop->last"
                        />
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="flex items-center gap-3 mt-4">
            <x-filament::button wire:click="save" icon="heroicon-o-check">
                {{ l('حفظ', 'Save') }}
            </x-filament::button>
            <x-filament::button wire:click="resetOrder" color="gray" outlined icon="heroicon-o-arrow-path">
                {{ l('إعادة التعيين', 'Reset') }}
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
