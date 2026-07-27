<x-filament-panels::page>
    @php($l = fn (string $ar, string $en) => app()->getLocale() === 'ar' ? $ar : $en)

    @if (auth()->user()->can('stock.import'))
    <form wire:submit.prevent="runPreview" class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $l('معاينة', 'Preview') }}</span>
                <span wire:loading>{{ $l('جاري الفحص…', 'Validating…') }}</span>
            </x-filament::button>

            <x-filament::button color="gray" tag="button" type="button" wire:click="downloadTemplate">
                {{ $l('تنزيل القالب', 'Download Template') }}
            </x-filament::button>
        </div>
    </form>

    @if ($preview !== null)
        <x-filament::section :heading="$l('نتيجة المعاينة', 'Preview Result')">
            <div class="flex gap-6 mb-4">
                <span class="font-bold text-success-600">
                    {{ $l('صفوف صالحة:', 'Valid rows:') }} {{ count($preview['valid']) }}
                </span>
                <span class="font-bold text-danger-600">
                    {{ $l('صفوف مرفوضة:', 'Rejected rows:') }} {{ count($preview['errors']) }}
                </span>
            </div>

            @if ($preview['errors'] !== [])
                <ul class="mb-4 list-disc ps-5 text-sm text-danger-600">
                    @foreach ($preview['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($preview['valid'] !== [])
                @if ($requiresApproval)
                    <div class="mb-4 rounded-lg border border-warning-300 bg-warning-50 p-3 text-sm text-warning-800">
                        {{ $l(
                            'يتضمن الملف رصيداً افتتاحياً أو فرقاً كبيراً ويتطلب اعتماد مدير المبيعات قبل التنفيذ.',
                            'This file contains an opening balance or large variance and requires sales-manager approval before execution.',
                        ) }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-start border-b">
                                <th class="p-2 text-start">SKU</th>
                                <th class="p-2 text-start">{{ $l('الرصيد الحالي', 'Current') }}</th>
                                <th class="p-2 text-start">{{ $l('العد الجديد', 'New Count') }}</th>
                                <th class="p-2 text-start">{{ $l('عبور (للعرض فقط)', 'Transit (read-only)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview['valid'] as $row)
                                <tr class="border-b">
                                    <td class="p-2">{{ $row['sku'] }}</td>
                                    <td class="p-2">{{ number_format($row['current'], 3) }}</td>
                                    <td class="p-2 font-semibold">{{ number_format($row['quantity'], 3) }}</td>
                                    <td class="p-2 text-gray-500">{{ $row['transit'] !== null ? number_format($row['transit'], 3) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    @if ($requiresApproval && !$approved && auth()->user()->can('stock.adjust'))
                        <x-filament::button
                            color="warning"
                            wire:click="approveImport"
                            wire:loading.attr="disabled"
                            wire:confirm="{{ $l(
                                'أنت تعتمد رصيداً افتتاحياً أو فرق مخزون كبير. سيتم تسجيل هويتك ووقت الاعتماد. متابعة؟',
                                'You are approving an opening balance or large stock variance. Your identity and approval time will be recorded. Continue?',
                            ) }}"
                        >
                            {{ $l('اعتماد التسوية', 'Approve Adjustment') }}
                        </x-filament::button>
                    @endif

                    <x-filament::button
                        color="danger"
                        wire:click="confirmImport"
                        wire:loading.attr="disabled"
                        @disabled($requiresApproval && !$approved)
                        wire:confirm="{{ $l(
                            'سيتم تعديل أرصدة المخزون لتطابق العد المستورد وإنشاء حركات مخزون مطابقة. لا يمكن التراجع. متابعة؟',
                            'Stock balances will be adjusted to match the imported counts and matching stock movements will be created. This cannot be undone. Continue?',
                        ) }}"
                    >
                        <span wire:loading.remove>{{ $l('تأكيد الاستيراد', 'Confirm Import') }}</span>
                        <span wire:loading>{{ $l('جاري الاستيراد…', 'Importing…') }}</span>
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif
    @endif

    @if (auth()->user()->can('stock.adjust'))
        <x-filament::section :heading="$l('تسويات تنتظر الاعتماد', 'Adjustments awaiting approval')">
            @if ($this->pendingApprovals->isEmpty())
                <p class="text-sm text-gray-500">{{ $l('لا توجد تسويات معلقة.', 'No pending adjustments.') }}</p>
            @else
                <div class="space-y-3">
                    @foreach ($this->pendingApprovals as $pending)
                        <div class="flex items-center justify-between gap-4 rounded-lg border p-3">
                            <div class="text-sm">
                                <strong>{{ $pending->warehouse?->name_ar }}</strong>
                                <span>— {{ $pending->stagedBy?->name }}</span>
                                <span>— {{ count($pending->parsed_rows) }} {{ $l('صف', 'rows') }}</span>
                            </div>
                            <x-filament::button
                                color="warning"
                                wire:click="approvePendingImport({{ $pending->id }})"
                                wire:confirm="{{ $l(
                                    'أنت تعتمد رصيداً افتتاحياً أو فرق مخزون كبير. سيتم تسجيل هويتك ووقت الاعتماد. متابعة؟',
                                    'You are approving an opening balance or large stock variance. Your identity and approval time will be recorded. Continue?',
                                ) }}"
                            >
                                {{ $l('اعتماد', 'Approve') }}
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endif

    <x-filament::section :heading="$l('آخر عمليات الاستيراد', 'Recent Imports')">
        @if ($this->recentImports->isEmpty())
            <p class="text-sm text-gray-500">{{ $l('لا توجد عمليات استيراد بعد', 'No imports yet') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2 text-start">{{ $l('الملف', 'File') }}</th>
                            <th class="p-2 text-start">{{ $l('المستودع', 'Warehouse') }}</th>
                            <th class="p-2 text-start">{{ $l('صفوف', 'Rows') }}</th>
                            <th class="p-2 text-start">{{ $l('مرفوض', 'Rejected') }}</th>
                            <th class="p-2 text-start">{{ $l('بواسطة', 'By') }}</th>
                            <th class="p-2 text-start">{{ $l('التاريخ', 'Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->recentImports as $log)
                            <tr class="border-b">
                                <td class="p-2">{{ $log->file_name }}</td>
                                <td class="p-2">{{ $log->warehouse?->name_ar }}</td>
                                <td class="p-2">{{ $log->rows_imported }}/{{ $log->rows_total }}</td>
                                <td class="p-2 {{ $log->rows_rejected > 0 ? 'text-danger-600' : '' }}">{{ $log->rows_rejected }}</td>
                                <td class="p-2">{{ $log->importedBy?->name }}</td>
                                <td class="p-2">{{ $log->imported_at?->translatedFormat('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
