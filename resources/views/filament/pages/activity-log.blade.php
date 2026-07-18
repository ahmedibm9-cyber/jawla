<div>
    <div class="mb-4">
        <select wire:model.live="typeFilter" class="border border-gray-300 rounded-lg p-2 text-sm">
            <option value="">{{ app()->getLocale() === 'ar' ? 'جميع الأنشطة' : 'All Activities' }}</option>
            <option value="invoice_created">{{ app()->getLocale() === 'ar' ? 'فاتورة جديدة' : 'Invoice Created' }}</option>
            <option value="invoice_submitted">{{ app()->getLocale() === 'ar' ? 'فاتورة مرسلة' : 'Invoice Submitted' }}</option>
            <option value="payment_collected">{{ app()->getLocale() === 'ar' ? 'دفعة محصلة' : 'Payment Collected' }}</option>
            <option value="invoice_reversed">{{ app()->getLocale() === 'ar' ? 'فاتورة ملغاة' : 'Invoice Reversed' }}</option>
            <option value="payment_reversed">{{ app()->getLocale() === 'ar' ? 'دفعة ملغاة' : 'Payment Reversed' }}</option>
        </select>
    </div>

    <div class="space-y-2">
        @foreach($activities as $a)
            <div class="bg-white rounded-lg border p-4 flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">{{ $a->created_at?->format('Y-m-d H:i') }}</span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ $a->is_reversed ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $a->type }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm">{{ $a->description }}</p>
                    <small class="text-gray-400">{{ $a->user?->name }}</small>
                </div>
                <div class="flex items-center gap-2">
                    @if(!$a->is_reversed && in_array($a->type, ['invoice_created', 'invoice_submitted', 'payment_collected']))
                        <button wire:click="reverse({{ $a->id }})" wire:confirm="{{ app()->getLocale() === 'ar' ? 'سيتم عكس هذا الإجراء. هل أنت متأكد؟' : 'This will reverse the action. Are you sure?' }}"
                            class="text-sm text-danger bg-transparent border border-danger rounded-lg px-3 py-1 cursor-pointer hover:bg-danger hover:text-white">
                            {{ app()->getLocale() === 'ar' ? 'إلغاء' : 'Reverse' }}
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $activities->links() }}
    </div>
</div>
