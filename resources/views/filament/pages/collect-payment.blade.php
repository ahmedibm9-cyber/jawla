<x-filament-panels::page>
    <div class="space-y-4">
        <form wire:submit="submit">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label>
                    <span>{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }} *</span>
                    <select wire:model="customer_id" class="rounded-md border-gray-300 w-full">
                        @foreach(\App\Models\Customer::where('company_id', auth()->user()->company_id)->where('is_active', true)->orderBy('name_ar')->limit(50)->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->name_ar }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>{{ app()->getLocale() === 'ar' ? 'فاتورة (اختياري)' : 'Invoice (optional)' }}</span>
                    <select wire:model="invoice_id" class="rounded-md border-gray-300 w-full">
                        <option value="">{{ app()->getLocale() === 'ar' ? 'بدون' : 'None' }}</option>
                        @if($customer_id)
                            @foreach(\App\Models\Invoice::where('customer_id', $customer_id)->whereIn('status', ['submitted','partially_paid'])->whereRaw('remaining_amount > 0')->get() as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->invoice_number }} ({{ $inv->remaining_amount }})</option>
                            @endforeach
                        @endif
                    </select>
                </label>

                <label>
                    <span>{{ app()->getLocale() === 'ar' ? 'المبلغ' : 'Amount' }} *</span>
                    <input type="number" step="0.01" wire:model="amount" class="rounded-md border-gray-300 w-full">
                </label>

                <label>
                    <span>{{ app()->getLocale() === 'ar' ? 'طريقة الدفع' : 'Payment Method' }}</span>
                    <select wire:model="method" class="rounded-md border-gray-300 w-full">
                        <option value="cash">{{ app()->getLocale() === 'ar' ? 'نقدي' : 'Cash' }}</option>
                        <option value="cheque">{{ app()->getLocale() === 'ar' ? 'شيك' : 'Cheque' }}</option>
                        <option value="transfer">{{ app()->getLocale() === 'ar' ? 'تحويل' : 'Transfer' }}</option>
                        <option value="other">{{ app()->getLocale() === 'ar' ? 'أخرى' : 'Other' }}</option>
                    </select>
                </label>

                <label class="md:col-span-2">
                    <span>{{ app()->getLocale() === 'ar' ? 'ملاحظات' : 'Notes' }}</span>
                    <input type="text" wire:model="notes" class="rounded-md border-gray-300 w-full">
                </label>
            </div>
            <button type="submit" class="mt-4 inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-md font-semibold">
                {{ app()->getLocale() === 'ar' ? 'تحصيل' : 'Collect' }}
            </button>
        </form>
    </div>
</x-filament-panels::page>