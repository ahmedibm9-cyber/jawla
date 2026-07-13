<?php

use Filament\Forms\Components\Tabs;

?>

<div class="space-y-4">
    <div class="flex gap-2 items-center">
        <input type="date" wire:model="fromDate" class="rounded-md border-gray-300 shadow-sm" placeholder="{{ __('From') }}">
        <input type="date" wire:model="toDate" class="rounded-md border-gray-300 shadow-sm" placeholder="{{ __('To') }}">
    </div>

    <div class="flex gap-2 border-b">
        <button wire:click="$set('tab','visit_reports')" class="px-4 py-2 {{ $tab === 'visit_reports' ? 'bg-gray-100 font-semibold' : '' }}">{{ app()->getLocale() === 'ar' ? 'زيارات' : 'Visits' }}</button>
        <button wire:click="$set('tab','quotations')" class="px-4 py-2 {{ $tab === 'quotations' ? 'bg-gray-100 font-semibold' : '' }}">{{ app()->getLocale() === 'ar' ? 'عروض أسعار' : 'Quotations' }}</button>
        <button wire:click="$set('tab','proformas')" class="px-4 py-2 {{ $tab === 'proformas' ? 'bg-gray-100 font-semibold' : '' }}">{{ app()->getLocale() === 'ar' ? 'فواتير مبدئية' : 'Proformas' }}</button>
        <button wire:click="$set('tab','invoices')" class="px-4 py-2 {{ $tab === 'invoices' ? 'bg-gray-100 font-semibold' : '' }}">{{ app()->getLocale() === 'ar' ? 'فواتير' : 'Invoices' }}</button>
    </div>

    @if($tab === 'visit_reports')
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'المندوب' : 'Rep' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'التاريخ' : 'Date' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'الملخص' : 'Summary' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->visits as $vr)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $vr->visit?->user?->name }}</a></td>
                        <td class="px-2 py-1">{{ $vr->visit?->customer?->name_ar }}</td>
                        <td class="px-2 py-1">{{ $vr->submitted_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-2 py-1">{{ Str::limit($vr->summary, 50) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-400 py-4">{{ __('No results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif($tab === 'quotations')
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'المنتج' : 'Product' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'الكمية' : 'Qty' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'السعر' : 'Price' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'الحالة' : 'Status' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->quotations as $r)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $r->customer?->name_ar }}</td>
                        <td class="px-2 py-1">{{ $r->product?->name_ar }}</td>
                        <td class="px-2 py-1">{{ $r->quantity_requested }}</td>
                        <td class="px-2 py-1">{{ $r->quotation?->base_price }}</td>
                        <td class="px-2 py-1">{{ $r->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-400 py-4">{{ __('No results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif($tab === 'proformas')
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'رقم' : 'Number' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'التاريخ' : 'Date' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->proformas as $p)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $p->proforma_number }}</td>
                        <td class="px-2 py-1">{{ $p->customer?->name_ar }}</td>
                        <td class="px-2 py-1">{{ number_format((float)$p->total, 2) }}</td>
                        <td class="px-2 py-1">{{ $p->posting_date?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-400 py-4">{{ __('No results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif($tab === 'invoices')
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'رقم' : 'Number' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'المتبقي' : 'Remaining' }}</th>
                    <th class="px-2 py-1 text-left">{{ app()->getLocale() === 'ar' ? 'الحالة' : 'Status' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->invoices as $inv)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $inv->invoice_number }}</td>
                        <td class="px-2 py-1">{{ $inv->customer?->name_ar }}</td>
                        <td class="px-2 py-1">{{ number_format((float)$inv->total, 2) }}</td>
                        <td class="px-2 py-1">{{ number_format((float)$inv->remaining_amount, 2) }}</td>
                        <td class="px-2 py-1">{{ $inv->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-400 py-4">{{ __('No results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</div>