<x-filament-panels::page>
    <div class="space-y-4">
        <form wire:submit="submit">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament-forms::selectwire name="customer_id" wire:model="customer_id" label="{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}" required />
                <x-filament-forms::selectwire name="invoice_id" wire:model="invoice_id" label="{{ app()->getLocale() === 'ar' ? 'فاتورة (اختياري)' : 'Invoice (optional)' }}" />
                <x-filament-forms::text-input wire:model="amount" name="amount" type="number" step="0.01" label="{{ app()->getLocale() === 'ar' ? 'المبلغ' : 'Amount' }}" required />
                <x-filament-forms::selectwire name="method" wire:model="method" label="{{ app()->getLocale() === 'ar' ? 'طريقة الدفع' : 'Payment Method' }}">
                    <option value="cash">{{ app()->getLocale() === 'ar' ? 'نقدي' : 'Cash' }}</option>
                    <option value="cheque">{{ app()->getLocale() === 'ar' ? 'شيك' : 'Cheque' }}</option>
                    <option value="transfer">{{ app()->getLocale() === 'ar' ? 'تحويل' : 'Transfer' }}</option>
                    <option value="other">{{ app()->getLocale() === 'ar' ? 'أخرى' : 'Other' }}</option>
                </x-filament-forms::selectwire>
                <x-filament-forms::text-input wire:model="notes" name="notes" label="{{ app()->getLocale() === 'ar' ? 'ملاحظات' : 'Notes' }}" />
            </div>
            <button type="submit" class="mt-4 inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-md font-semibold">
                {{ app()->getLocale() === 'ar' ? 'تحصيل' : 'Collect' }}
            </button>
        </form>
    </div>
</x-filament-panels::page>