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

        {{-- Summary Cards --}}
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

        {{-- Visit Trend Chart --}}
        <div class="card mb-4">
            <h3 class="font-semibold text-sm mb-3">{{ __('app.visit_trend') }}</h3>
            <canvas id="visitTrendChart" height="180"></canvas>
        </div>

        {{-- Revenue Trend Chart --}}
        <div class="card mb-4">
            <h3 class="font-semibold text-sm mb-3">{{ __('app.revenue_trend') }}</h3>
            <canvas id="revenueTrendChart" height="180"></canvas>
        </div>

        {{-- Todo Completion Ring --}}
        <div class="card mb-4">
            <h3 class="font-semibold text-sm mb-3">{{ __('app.todo_completion') }}</h3>
            <div class="flex items-center gap-4">
                <div class="relative" style="width:100px;height:100px">
                    <canvas id="todoRing" width="100" height="100"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-lg font-bold">{{ $metrics['totalTodos'] > 0 ? round(($metrics['completedTodos'] / max($metrics['totalTodos'], 1)) * 100) : 0 }}%</span>
                    </div>
                </div>
                <div class="text-sm text-text-secondary">
                    <p>{{ $metrics['completedTodos'] }} {{ __('app.completed') }}</p>
                    <p>{{ $metrics['totalTodos'] - $metrics['completedTodos'] }} {{ __('app.remaining') }}</p>
                </div>
            </div>
        </div>
    </div>

    <x-tab-bar active="more" />

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('livewire:initialized', () => {
        const period = @js($period);
        const userId = @js(auth()->id());
        const companyId = @js(auth()->user()->company_id);

        // Fetch daily data for the chart
        fetch(`/app/performance/data?period=${period}`)
            .then(r => r.json())
            .then(data => {
                // Visit trend
                new Chart(document.getElementById('visitTrendChart'), {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: @js(__('app.visits')),
                            data: data.visits,
                            backgroundColor: '#6DB83B',
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // Revenue trend
                new Chart(document.getElementById('revenueTrendChart'), {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: @js(__('app.revenue')),
                            data: data.revenue,
                            borderColor: '#D97706',
                            backgroundColor: 'rgba(217,119,6,0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true },
                            x: { grid: { display: false } }
                        }
                    }
                });
            });

        // Todo completion ring
        const todoCtx = document.getElementById('todoRing');
        if (todoCtx) {
            const completed = @js($metrics['completedTodos']);
            const total = @js($metrics['totalTodos']);
            const remaining = Math.max(total - completed, 0);
            new Chart(todoCtx, {
                type: 'doughnut',
                data: {
                    labels: [@js(__('app.completed')), @js(__('app.remaining'))],
                    datasets: [{
                        data: [completed, remaining],
                        backgroundColor: ['#6DB83B', '#E5E7EB'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: { legend: { display: false } },
                    responsive: false,
                }
            });
        }
    });
    </script>
    @endpush
</div>
