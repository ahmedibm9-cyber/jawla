<x-filament-panels::page>
    @php
        $config = $this->getConfigStatus();
        $submissions = $this->getRecentSubmissions();
    @endphp

    <div class="grid gap-6">
        {{-- Configuration Status --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ l('حالة التكوين', 'Configuration Status') }}
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        :icon="$config['enabled'] ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
                        :class="$config['enabled'] ? 'text-success-500' : 'text-danger-500'"
                        width="h-5 w-5"
                    />
                    <span>{{ l('تفعيل الفوترة الإلكترونية', 'ETA Enabled') }}:</span>
                    <span class="font-semibold">{{ $config['enabled'] ? l('نعم', 'Yes') : l('لا', 'No') }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-globe-alt"
                        class="text-gray-500"
                        width="h-5 w-5"
                    />
                    <span>{{ l('البيئة', 'Environment') }}:</span>
                    <span class="font-semibold">{{ ucfirst($config['environment']) }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <x-filament::icon
                        :icon="$config['has_client_id'] ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
                        :class="$config['has_client_id'] ? 'text-success-500' : 'text-warning-500'"
                        width="h-5 w-5"
                    />
                    <span>{{ l('معرف العميل', 'Client ID') }}:</span>
                    <span class="font-semibold">{{ $config['has_client_id'] ? l('تم الإعداد', 'Configured') : l('غير معد', 'Not set') }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <x-filament::icon
                        :icon="$config['has_taxpayer_rin'] ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
                        :class="$config['has_taxpayer_rin'] ? 'text-success-500' : 'text-warning-500'"
                        width="h-5 w-5"
                    />
                    <span>{{ l('رقم التسجيل الضريبي', 'Taxpayer RIN') }}:</span>
                    <span class="font-semibold">{{ $config['has_taxpayer_rin'] ? l('تم الإعداد', 'Configured') : l('غير معد', 'Not set') }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <x-filament::icon
                        :icon="$config['has_api_url'] ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
                        :class="$config['has_api_url'] ? 'text-success-500' : 'text-warning-500'"
                        width="h-5 w-5"
                    />
                    <span>{{ l('رابط API', 'API URL') }}:</span>
                    <span class="font-semibold">{{ $config['has_api_url'] ? l('تم الإعداد', 'Configured') : l('غير معد', 'Not set') }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <x-filament::icon
                        :icon="$config['has_id_url'] ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'"
                        :class="$config['has_id_url'] ? 'text-success-500' : 'text-warning-500'"
                        width="h-5 w-5"
                    />
                    <span>{{ l('رابط المصادقة', 'Auth URL') }}:</span>
                    <span class="font-semibold">{{ $config['has_id_url'] ? l('تم الإعداد', 'Configured') : l('غير معد', 'Not set') }}</span>
                </div>
            </div>
        </x-filament::section>

        {{-- Recent Submissions --}}
        <x-filament::section>
            <x-slot name="heading">
                {{ l('آخر الإرسالات', 'Recent Submissions') }}
            </x-slot>

            @if($submissions->isEmpty())
                <div class="text-center py-8 text-gray-500">
                    {{ l('لا توجد إرسالات بعد', 'No submissions yet') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 px-3">{{ l('رقم الفاتورة', 'Invoice') }}</th>
                                <th class="text-left py-2 px-3">{{ l('الشركة', 'Company') }}</th>
                                <th class="text-left py-2 px-3">{{ l('الحالة', 'Status') }}</th>
                                <th class="text-left py-2 px-3">{{ l('التاريخ', 'Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $invoice)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-3 font-mono text-xs">{{ $invoice->invoice_number }}</td>
                                    <td class="py-2 px-3">{{ $invoice->company->name_en ?? $invoice->company->name_ar ?? '—' }}</td>
                                    <td class="py-2 px-3">
                                        @php
                                            $color = match($invoice->eta_status) {
                                                'accepted' => 'success',
                                                'submitted' => 'info',
                                                'rejected' => 'danger',
                                                default => 'gray',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                                            {{ $invoice->eta_status }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-xs text-gray-500">
                                        {{ $invoice->eta_submitted_at?->format('Y-m-d H:i') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
