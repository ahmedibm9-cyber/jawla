<div class="space-y-6">
    {{-- Filter --}}
    <div class="filament-section rounded-xl bg-white dark:bg-gray-900 shadow-md border border-gray-200 dark:border-white/10 p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ app()->getLocale() === 'ar' ? 'نوع النشاط' : 'Activity Type' }}</label>
                <select wire:model.live="typeFilter" class="w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ app()->getLocale() === 'ar' ? 'جميع الأنشطة' : 'All Activities' }}</option>
                    <option value="invoice_created">{{ app()->getLocale() === 'ar' ? 'فاتورة جديدة' : 'Invoice Created' }}</option>
                    <option value="invoice_submitted">{{ app()->getLocale() === 'ar' ? 'فاتورة مرسلة' : 'Invoice Submitted' }}</option>
                    <option value="payment_collected">{{ app()->getLocale() === 'ar' ? 'دفعة محصلة' : 'Payment Collected' }}</option>
                    <option value="invoice_reversed">{{ app()->getLocale() === 'ar' ? 'فاتورة ملغاة' : 'Invoice Reversed' }}</option>
                    <option value="payment_reversed">{{ app()->getLocale() === 'ar' ? 'دفعة ملغاة' : 'Payment Reversed' }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Activity List --}}
    <div class="space-y-3">
        @forelse($activities as $a)
            <div class="filament-section rounded-xl bg-white dark:bg-gray-900 shadow-md border border-gray-200 dark:border-white/10 p-4">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $a->created_at?->format('Y-m-d H:i') }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $a->is_reversed ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">{{ $a->type }}</span>
                            @if($a->is_reversed)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ app()->getLocale() === 'ar' ? 'ملغى' : 'Reversed' }}</span>
                            @endif
                        </div>
                        <p class="text-sm dark:text-white">{{ $a->description }}</p>
                        <span class="text-xs text-gray-400">{{ $a->user?->name }}</span>
                    </div>
                    <div class="flex-shrink-0">
                        @if(!$a->is_reversed && in_array($a->type, ['invoice_created', 'invoice_submitted', 'payment_collected']))
                            <button wire:click="reverse({{ $a->id }})" wire:confirm="{{ app()->getLocale() === 'ar' ? 'سيتم عكس هذا الإجراء. هل أنت متأكد؟' : 'This will reverse the action. Are you sure?' }}"
                                class="inline-flex items-center px-3 py-1.5 border border-red-300 text-red-700 bg-white hover:bg-red-50 rounded-lg text-sm font-medium transition dark:border-red-700 dark:text-red-400 dark:bg-transparent dark:hover:bg-red-900/20">
                                {{ app()->getLocale() === 'ar' ? 'إلغاء' : 'Reverse' }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="filament-section rounded-xl bg-white dark:bg-gray-900 shadow-md border border-gray-200 dark:border-white/10 p-8 text-center text-gray-400">
                {{ app()->getLocale() === 'ar' ? 'لا يوجد أنشطة' : 'No activities found' }}
            </div>
        @endforelse
    </div>

    <div>
        {{ $activities->links() }}
    </div>
</div>
