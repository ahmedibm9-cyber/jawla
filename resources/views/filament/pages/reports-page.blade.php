<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Date Range Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-3 items-end">
                <x-filament::input
                    wire:model.live="fromDate"
                    type="date"
                    :label="l('من', 'From')"
                />
                <x-filament::button wire:click="exportCsv" icon="heroicon-o-arrow-down-tray">
                    {{ l('تصدير CSV', 'Export CSV') }}
                </x-filament::button>
                <x-filament::input
                    wire:model.live="toDate"
                    type="date"
                    :label="l('إلى', 'To')"
                />
            </div>
        </x-filament::section>

        {{-- Tabs --}}
        <x-filament::tabs>
            @can('reports.visits')
            <x-filament::tabs.item
                wire:click="$set('tab', 'visit_reports')"
                :active="$tab === 'visit_reports'"
            >
                {{ l('زيارات', 'Visits') }}
            </x-filament::tabs.item>
            @endcan
            @can('reports.sales')
            <x-filament::tabs.item
                wire:click="$set('tab', 'quotations')"
                :active="$tab === 'quotations'"
            >
                {{ l('عروض أسعار', 'Quotations') }}
            </x-filament::tabs.item>
            <x-filament::tabs.item
                wire:click="$set('tab', 'proformas')"
                :active="$tab === 'proformas'"
            >
                {{ l('فواتير مبدئية', 'Proformas') }}
            </x-filament::tabs.item>
            @endcan
            @can('reports.financial')
            <x-filament::tabs.item
                wire:click="$set('tab', 'invoices')"
                :active="$tab === 'invoices'"
            >
                {{ l('فواتير', 'Invoices') }}
            </x-filament::tabs.item>
            @endcan
        </x-filament::tabs>

        {{-- Tab Content --}}
        <x-filament::section>
            @if($tab === 'visit_reports')
                <div class="overflow-x-auto">
                    <table class="filament-table w-full text-sm" aria-label="{{ l('تقرير الزيارات', 'Visit reports') }}">
                        <thead>
                            <tr>
                                <th class="filament-table-header-cell">{{ l('المندوب', 'Rep') }}</th>
                                <th class="filament-table-header-cell">{{ l('العميل', 'Customer') }}</th>
                                <th class="filament-table-header-cell">{{ l('التاريخ', 'Date') }}</th>
                                <th class="filament-table-header-cell">{{ l('الملخص', 'Summary') }}</th>
                            </tr>
                        </thead>
                        <tbody class="filament-table-body divide-y">
                            @forelse($this->visits as $vr)
                                <tr class="filament-table-row">
                                    <td class="filament-table-cell">{{ $vr->visit?->user?->name }}</td>
                                    <td class="filament-table-cell">{{ $vr->visit?->customer?->name_ar }}</td>
                                    <td class="filament-table-cell text-gray-500">{{ $vr->submitted_at?->format('Y-m-d H:i') }}</td>
                                    <td class="filament-table-cell text-gray-500">{{ Str::limit($vr->summary, 50) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="filament-table-cell text-center py-8">{{ __('app.no_results') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($this->visits->hasPages())
                    <div class="mt-4">
                        {{ $this->visits->links() }}
                    </div>
                @endif

            @elseif($tab === 'quotations')
                <div class="overflow-x-auto">
                    <table class="filament-table w-full text-sm" aria-label="{{ l('عروض الأسعار', 'Quotations') }}">
                        <thead>
                            <tr>
                                <th class="filament-table-header-cell">{{ __('app.customer') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.product') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.quantity') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.price') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="filament-table-body divide-y">
                            @forelse($this->quotations as $r)
                                <tr class="filament-table-row">
                                    <td class="filament-table-cell">{{ $r->customer?->name_ar }}</td>
                                    <td class="filament-table-cell">{{ $r->product?->name_ar }}</td>
                                    <td class="filament-table-cell">{{ $r->quantity_requested }}</td>
                                    <td class="filament-table-cell">{{ $r->quotation?->base_price }}</td>
                                    <td class="filament-table-cell"><x-filament::badge :color="$r->status === 'approved' ? 'success' : ($r->status === 'rejected' ? 'danger' : 'gray')" size="sm">{{ $r->status }}</x-filament::badge></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="filament-table-cell text-center py-8">{{ __('app.no_results') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($this->quotations->hasPages())
                    <div class="mt-4">{{ $this->quotations->links() }}</div>
                @endif

            @elseif($tab === 'proformas')
                <div class="overflow-x-auto">
                    <table class="filament-table w-full text-sm" aria-label="{{ l('الفاتورات المبدئية', 'Proformas') }}">
                        <thead>
                            <tr>
                                <th class="filament-table-header-cell">{{ __('app.number') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.customer') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.total') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="filament-table-body divide-y">
                            @forelse($this->proformas as $p)
                                <tr class="filament-table-row">
                                    <td class="filament-table-cell font-mono">{{ $p->proforma_number }}</td>
                                    <td class="filament-table-cell">{{ $p->customer?->name_ar }}</td>
                                    <td class="filament-table-cell font-medium">{{ number_format((float)$p->total, 2) }}</td>
                                    <td class="filament-table-cell text-gray-500">{{ $p->posting_date?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="filament-table-cell text-center py-8">{{ __('app.no_results') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($this->proformas->hasPages())
                    <div class="mt-4">{{ $this->proformas->links() }}</div>
                @endif

            @elseif($tab === 'invoices')
                <div class="overflow-x-auto">
                    <table class="filament-table w-full text-sm" aria-label="{{ l('الفواتير', 'Invoices') }}">
                        <thead>
                            <tr>
                                <th class="filament-table-header-cell">{{ __('app.number') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.customer') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.total') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.remaining') }}</th>
                                <th class="filament-table-header-cell">{{ __('app.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="filament-table-body divide-y">
                            @forelse($this->invoices as $inv)
                                <tr class="filament-table-row">
                                    <td class="filament-table-cell font-mono">{{ $inv->invoice_number }}</td>
                                    <td class="filament-table-cell">{{ $inv->customer?->name_ar }}</td>
                                    <td class="filament-table-cell font-medium">{{ number_format((float)$inv->total, 2) }}</td>
                                    <td class="filament-table-cell">{{ number_format((float)$inv->remaining_amount, 2) }}</td>
                                    <td class="filament-table-cell"><x-filament::badge :color="$inv->status === 'submitted' ? 'success' : ($inv->status === 'cancelled' ? 'danger' : 'gray')" size="sm">{{ $inv->status }}</x-filament::badge></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="filament-table-cell text-center py-8">{{ __('app.no_results') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($this->invoices->hasPages())
                    <div class="mt-4">{{ $this->invoices->links() }}</div>
                @endif
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
