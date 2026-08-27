<div class="main-content">
    <x-page-header :title="__('app.calendar')">
        <x-slot:icon><x-heroicon-o-calendar class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        <div class="flex justify-between items-center mb-4">
            <button type="button" wire:click="previousMonth" class="btn btn-secondary">
                <x-heroicon-m-chevron-left class="w-5 h-5" />
            </button>
            <h2 class="font-semibold text-lg">{{ now()->parse($currentMonth . '-01')->format('F Y') }}</h2>
            <button type="button" wire:click="nextMonth" class="btn btn-secondary">
                <x-heroicon-m-chevron-right class="w-5 h-5" />
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 mb-4">
            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                <div class="text-center text-xs font-medium text-text-muted py-2">{{ $day }}</div>
            @endforeach

            @foreach($days as $day)
                <button type="button"
                        wire:click="selectDate('{{ $day['date'] }}')"
                        class="aspect-square flex flex-col items-center justify-center rounded-lg text-sm @class([
                            'bg-primary text-white' => $selectedDate === $day['date'],
                            'bg-primary/10 text-primary font-semibold' => $day['isToday'] && $selectedDate !== $day['date'],
                            'hover:bg-gray-100 dark:hover:bg-gray-800' => !$day['isToday'] && $selectedDate !== $day['date'],
                        ])">
                    {{ $day['day'] }}
                    @if($day['hasEvents'])
                        <span class="w-1 h-1 rounded-full bg-primary mt-0.5"></span>
                    @endif
                </button>
            @endforeach
        </div>

        @if($selectedDate)
            <h3 class="font-semibold text-base mb-3">{{ __('app.events_for') }} {{ now()->parse($selectedDate)->format('M d') }}</h3>

            @forelse($events as $event)
                <article class="card mb-3">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0">
                            <h4 class="font-medium text-sm">{{ $event['title'] }}</h4>
                            <p class="text-xs text-text-muted mt-1">{{ $event['time'] }}</p>
                        </div>
                        <span class="badge @class([
                            'badge-success' => $event['status'] === 'completed' || $event['status'] === 'approved',
                            'badge-danger' => $event['status'] === 'cancelled',
                            'badge-warning' => $event['status'] === 'in_progress' || $event['status'] === 'pending',
                            'badge-info' => $event['status'] === 'new' || $event['status'] === 'open',
                            'badge-neutral' => ! in_array($event['status'], ['completed', 'approved', 'cancelled', 'in_progress', 'pending', 'new', 'open'], true),
                        ])">{{ $event['status'] }}</span>
                    </div>
                    @if(isset($event['amount']))
                        <p class="text-sm font-semibold mt-2">{{ number_format($event['amount'], 2) }}</p>
                    @endif
                </article>
            @empty
                <x-ds.empty icon="heroicon-o-calendar" :message="__('app.no_events')" />
            @endforelse
        @endif
    </div>

    <x-tab-bar active="more" />
</div>
