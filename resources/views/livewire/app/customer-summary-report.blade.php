<div class="main-content">
    <x-page-header :title="__('app.customer_summary')">
        <x-slot:icon><x-heroicon-o-users class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        {{-- Metrics Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="card text-center">
                <p class="text-2xl font-bold">{{ $data['metrics']['total'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.total_customers') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold text-green-600">{{ $data['metrics']['active'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.active') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold text-blue-600">{{ $data['metrics']['newThisMonth'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.new_this_month') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold text-red-600">{{ $data['metrics']['overdue'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.overdue_balance') }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="flex gap-2 mb-4">
            <button type="button" wire:click="$set('statusFilter', 'all')" class="btn {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.all') }}
            </button>
            <button type="button" wire:click="$set('statusFilter', 'active')" class="btn {{ $statusFilter === 'active' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.active') }}
            </button>
            <button type="button" wire:click="$set('statusFilter', 'inactive')" class="btn {{ $statusFilter === 'inactive' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.inactive') }}
            </button>
            <button type="button" wire:click="exportCsv" class="btn btn-secondary">
                CSV ↓
            </button>
        </div>

        {{-- Customer Table --}}
        <div class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-text-muted">
                        <th class="text-left py-2 px-2 cursor-pointer" wire:click="toggleSort('name_ar')">
                            {{ __('app.name') }}
                            @if($sortBy === 'name_ar') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </th>
                        <th class="text-left py-2 px-2 hidden sm:table-cell">{{ __('app.phone') }}</th>
                        <th class="text-center py-2 px-2 cursor-pointer" wire:click="toggleSort('balance')">
                            {{ __('app.balance') }}
                            @if($sortBy === 'balance') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </th>
                        <th class="text-center py-2 px-2">{{ __('app.invoices') }}</th>
                        <th class="text-center py-2 px-2">{{ __('app.visits') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['customers'] as $customer)
                        <tr class="border-b last:border-b-0">
                            <td class="py-2 px-2">
                                <div class="font-medium">{{ l($customer->name_ar, $customer->name_en ?? $customer->name_ar) }}</div>
                                <div class="text-xs text-text-muted">{{ $customer->code }}</div>
                            </td>
                            <td class="py-2 px-2 hidden sm:table-cell text-text-secondary">{{ $customer->phone }}</td>
                            <td class="py-2 px-2 text-center">
                                @if((float) $customer->balance > 0)
                                    <span class="text-red-600 font-medium">{{ number_format((float) $customer->balance, 2) }}</span>
                                @else
                                    <span class="text-text-muted">0.00</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-center">{{ $customer->invoices_count }}</td>
                            <td class="py-2 px-2 text-center">{{ $customer->visits_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-text-muted">{{ __('app.no_customers') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-tab-bar active="more" />
</div>
