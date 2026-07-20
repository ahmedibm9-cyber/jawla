@php($ar = app()->getLocale() === 'ar')
<x-filament-panels::page>
    <div class="space-y-6">
        @forelse($this->groups as $group)
            @php($product = $group['product'])
            @php($offers = $group['offers'])
            @php($best = $offers->first())
            <x-filament::section>
                <x-slot name="heading">
                    {{ $ar ? $product?->name_ar : ($product?->name_en ?? $product?->name_ar) }}
                    <span class="text-sm text-gray-500">({{ $offers->count() }} {{ $ar ? 'عرض' : 'offers' }})</span>
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-start text-gray-500">
                                <th class="p-2 text-start">{{ $ar ? 'المورد' : 'Supplier' }}</th>
                                <th class="p-2 text-start">{{ $ar ? 'السعر' : 'Price' }}</th>
                                <th class="p-2 text-start">{{ $ar ? 'الكمية' : 'Qty' }}</th>
                                <th class="p-2 text-start">{{ $ar ? 'شروط الدفع' : 'Terms' }}</th>
                                <th class="p-2 text-start">{{ $ar ? 'الانتهاء' : 'Expires' }}</th>
                                <th class="p-2 text-start">{{ $ar ? 'الحالة' : 'Status' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($offers as $offer)
                                <tr @class(['border-t', 'bg-success-50 dark:bg-success-500/10' => $offer->is($best)])>
                                    <td class="p-2">
                                        {{ $offer->supplier?->name_ar ?? $offer->supplier?->name_en ?? ($ar ? 'غير محدد' : 'N/A') }}
                                        @if($offer->is($best))
                                            <x-filament::badge color="success" class="ms-1">{{ $ar ? 'الأفضل' : 'Best' }}</x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="p-2 font-semibold" dir="ltr">{{ number_format((float) $offer->offered_price, 2) }} {{ $offer->currency }}</td>
                                    <td class="p-2" dir="ltr">{{ rtrim(rtrim(number_format((float) $offer->quantity, 3), '0'), '.') }}</td>
                                    <td class="p-2">{{ $offer->payment_terms ?: '—' }}</td>
                                    <td class="p-2">{{ $offer->expires_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="p-2">
                                        <x-filament::badge :color="$offer->status === 'sales_approved' ? 'info' : 'warning'">
                                            {{ __('app.status_'.$offer->status) }}
                                        </x-filament::badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <p class="text-gray-500">{{ $ar ? 'لا توجد عروض شراء مفتوحة للمقارنة.' : 'No open purchase offers to compare.' }}</p>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
