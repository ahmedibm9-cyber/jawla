<div class="main-content">
    <x-page-header :title="__('app.performance')">
        <x-slot:icon><x-heroicon-o-chart-bar class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        <div class="flex gap-2 mb-4" role="group" aria-label="{{ __('app.performance_filter') }}">
            <button type="button" wire:click="$set('period', 'today')" class="btn {{ $period === 'today' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.today') }}
            </button>
            <button type="button" wire:click="$set('period', 'week')" class="btn {{ $period === 'week' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.this_week') }}
            </button>
            <button type="button" wire:click="$set('period', 'month')" class="btn {{ $period === 'month' ? 'btn-primary' : 'btn-secondary' }} flex-1">
                {{ __('app.this_month') }}
            </button>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="card text-center">
                <p class="text-2xl font-bold">{{ $metrics['completedVisits'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.visits') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold">{{ $metrics['visitAchievement'] }}%</p>
                <p class="text-sm text-text-muted">{{ __('app.achievement') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold">{{ $metrics['newCustomers'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.new_customers') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold">{{ $metrics['totalInvoices'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.invoices') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold">{{ number_format($metrics['totalRevenue'], 2) }}</p>
                <p class="text-sm text-text-muted">{{ __('app.revenue') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold">{{ $metrics['totalReturns'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.returns') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold">{{ $metrics['totalCalls'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.calls') }}</p>
            </div>
            <div class="card text-center">
                <p class="text-2xl font-bold">{{ $metrics['completedTodos'] }}/{{ $metrics['totalTodos'] }}</p>
                <p class="text-sm text-text-muted">{{ __('app.todos') }}</p>
            </div>
        </div>
    </div>

    <x-tab-bar active="more" />
</div>
