<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-3 items-end">
                <div class="w-full sm:w-auto">
                    <label class="filament-component-label mb-1 block">{{ app()->getLocale() === 'ar' ? 'نوع النشاط' : 'Activity Type' }}</label>
                    <select wire:model.live="typeFilter" class="filament-input w-full">
                        <option value="">{{ app()->getLocale() === 'ar' ? 'جميع الأنشطة' : 'All Activities' }}</option>
                        <option value="invoice_created">{{ app()->getLocale() === 'ar' ? 'فاتورة جديدة' : 'Invoice Created' }}</option>
                        <option value="invoice_submitted">{{ app()->getLocale() === 'ar' ? 'فاتورة مرسلة' : 'Invoice Submitted' }}</option>
                        <option value="payment_collected">{{ app()->getLocale() === 'ar' ? 'دفعة محصلة' : 'Payment Collected' }}</option>
                        <option value="invoice_reversed">{{ app()->getLocale() === 'ar' ? 'فاتورة ملغاة' : 'Invoice Reversed' }}</option>
                        <option value="payment_reversed">{{ app()->getLocale() === 'ar' ? 'دفعة ملغاة' : 'Payment Reversed' }}</option>
                    </select>
                </div>
            </div>
        </x-filament::section>

        {{-- Activity List --}}
        <div class="space-y-3">
            @forelse($this->activities as $a)
                <x-filament::section>
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-xs text-gray-500">{{ $a->created_at?->format('Y-m-d H:i') }}</span>
                                <x-filament::badge :color="$a->is_reversed ? 'danger' : 'info'" size="sm">{{ $a->type }}</x-filament::badge>
                                @if($a->is_reversed)
                                    <x-filament::badge color="danger" size="sm">{{ app()->getLocale() === 'ar' ? 'ملغى' : 'Reversed' }}</x-filament::badge>
                                @endif
                            </div>
                            <p class="text-sm">{{ $a->description }}</p>
                            <span class="text-xs text-gray-400">{{ $a->user?->name }}</span>
                        </div>
                        <div class="flex-shrink-0">
                            @if(!$a->is_reversed && in_array($a->type, ['invoice_created', 'invoice_submitted', 'payment_collected']))
                                <x-filament::button
                                    wire:click="reverse({{ $a->id }})"
                                    wire:confirm="{{ app()->getLocale() === 'ar' ? 'سيتم عكس هذا الإجراء. هل أنت متأكد؟' : 'This will reverse the action. Are you sure?' }}"
                                    color="danger"
                                    size="sm"
                                    outlined
                                >
                                    {{ app()->getLocale() === 'ar' ? 'إلغاء' : 'Reverse' }}
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            @empty
                <x-filament::section>
                    <x-filament::empty-state icon="heroicon-o-clock" :heading="app()->getLocale() === 'ar' ? 'لا يوجد أنشطة' : 'No activities'" />
                </x-filament::section>
            @endforelse
        </div>

        @if($this->activities->hasPages())
            <div>
                {{ $this->activities->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
