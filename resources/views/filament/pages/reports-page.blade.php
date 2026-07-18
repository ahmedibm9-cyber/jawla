<?php

use Filament\Forms\Components\Tabs;

?>

<div class="space-y-6">
    {{-- Date Range Filter --}}
    <div class="filament-section rounded-xl bg-white dark:bg-gray-900 shadow-md border border-gray-200 dark:border-white/10 p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ app()->getLocale() === 'ar' ? 'من' : 'From' }}</label>
                <input type="date" wire:model.live="fromDate" class="w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">{{ app()->getLocale() === 'ar' ? 'إلى' : 'To' }}</label>
                <input type="date" wire:model.live="toDate" class="w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="filament-tabs flex gap-1 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg">
        <button wire:click="$set('tab','visit_reports')" class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition {{ $tab === 'visit_reports' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">{{ app()->getLocale() === 'ar' ? 'زيارات' : 'Visits' }}</button>
        <button wire:click="$set('tab','quotations')" class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition {{ $tab === 'quotations' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">{{ app()->getLocale() === 'ar' ? 'عروض أسعار' : 'Quotations' }}</button>
        <button wire:click="$set('tab','proformas')" class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition {{ $tab === 'proformas' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">{{ app()->getLocale() === 'ar' ? 'فواتير مبدئية' : 'Proformas' }}</button>
        <button wire:click="$set('tab','invoices')" class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition {{ $tab === 'invoices' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">{{ app()->getLocale() === 'ar' ? 'فواتير' : 'Invoices' }}</button>
    </div>

    {{-- Tab Content --}}
    <div class="filament-section rounded-xl bg-white dark:bg-gray-900 shadow-md border border-gray-200 dark:border-white/10 overflow-hidden">
        @if($tab === 'visit_reports')
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-800">
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'المندوب' : 'Rep' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'التاريخ' : 'Date' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'الملخص' : 'Summary' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($this->visits as $vr)
                        <tr class="dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3">{{ $vr->visit?->user?->name }}</td>
                            <td class="px-4 py-3">{{ $vr->visit?->customer?->name_ar }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $vr->submitted_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ Str::limit($vr->summary, 50) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-gray-400 py-8">{{ __('No results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($tab === 'quotations')
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-800">
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'المنتج' : 'Product' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'الكمية' : 'Qty' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'السعر' : 'Price' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'الحالة' : 'Status' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($this->quotations as $r)
                        <tr class="dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3">{{ $r->customer?->name_ar }}</td>
                            <td class="px-4 py-3">{{ $r->product?->name_ar }}</td>
                            <td class="px-4 py-3">{{ $r->quantity_requested }}</td>
                            <td class="px-4 py-3">{{ $r->quotation?->base_price }}</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $r->status === 'approved' ? 'bg-green-100 text-green-800' : ($r->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">{{ $r->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-gray-400 py-8">{{ __('No results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($tab === 'proformas')
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-800">
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'رقم' : 'Number' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'التاريخ' : 'Date' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($this->proformas as $p)
                        <tr class="dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-mono text-sm">{{ $p->proforma_number }}</td>
                            <td class="px-4 py-3">{{ $p->customer?->name_ar }}</td>
                            <td class="px-4 py-3 font-medium">{{ number_format((float)$p->total, 2) }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $p->posting_date?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-gray-400 py-8">{{ __('No results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($tab === 'invoices')
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-800">
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'رقم' : 'Number' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'العميل' : 'Customer' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'المتبقي' : 'Remaining' }}</th>
                        <th class="text-start px-4 py-3 font-medium text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'ar' ? 'الحالة' : 'Status' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($this->invoices as $inv)
                        <tr class="dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-mono text-sm">{{ $inv->invoice_number }}</td>
                            <td class="px-4 py-3">{{ $inv->customer?->name_ar }}</td>
                            <td class="px-4 py-3 font-medium">{{ number_format((float)$inv->total, 2) }}</td>
                            <td class="px-4 py-3">{{ number_format((float)$inv->remaining_amount, 2) }}</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $inv->status === 'submitted' ? 'bg-green-100 text-green-800' : ($inv->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">{{ $inv->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-gray-400 py-8">{{ __('No results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if($tab === 'visit_reports' && $this->visits->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-white/10">{{ $this->visits->links() }}</div>
        @elseif($tab === 'quotations' && $this->quotations->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-white/10">{{ $this->quotations->links() }}</div>
        @elseif($tab === 'proformas' && $this->proformas->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-white/10">{{ $this->proformas->links() }}</div>
        @elseif($tab === 'invoices' && $this->invoices->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-white/10">{{ $this->invoices->links() }}</div>
        @endif
    </div>
</div>
