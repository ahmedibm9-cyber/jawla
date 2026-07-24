<div>
<x-pull-to-refresh>
    <div class="main-content">
        {{-- Hero Header --}}
        <div class="home-hero">
            <div class="home-hero-content">
                <div class="home-hero-brand">
                    <img src="/images/green-j.webp" alt="Jawla" class="home-hero-logo logo-light" width="100" height="33">
                    <img src="/images/white-j.webp" alt="Jawla" class="home-hero-logo logo-dark" width="100" height="33">
                </div>
                <div class="home-hero-top">
                    <div>
                        <h1 class="home-hero-title">{{ __('app.welcome', ['name' => $user->name]) }}</h1>
                        <p class="home-hero-subtitle">{{ __('app.today_visits') }}: {{ $visitCount }}</p>
                    </div>
                    <div class="home-hero-avatar">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="home-stats">
            <div class="home-stat-card home-stat-pending">
                <div class="home-stat-number">{{ $pendingCount }}</div>
                <div class="home-stat-label">{{ __('app.visits_pending') }}</div>
            </div>
            <div class="home-stat-card home-stat-done">
                <div class="home-stat-number">{{ $completedCount }}</div>
                <div class="home-stat-label">{{ __('app.visits_done') }}</div>
            </div>
        </div>

        {{-- Today's Plan --}}
        <div class="home-section">
            <h3 class="home-section-title">{{ __('app.todays_plan') }}</h3>

            <div wire:loading.delay class="space-y-2 mb-3" aria-hidden="true">
                <x-ds.skeleton height="72px" />
                <x-ds.skeleton height="72px" />
            </div>

            @if($todayVisits->isEmpty())
                <x-ds.empty icon="heroicon-o-map-pin" :message="__('app.no_visits_yet')">
                    <x-slot:action>
                        <a href="/app" class="btn btn-primary no-underline">{{ __('app.back_home') }}</a>
                    </x-slot:action>
                </x-ds.empty>
            @else
                @foreach($todayVisits as $assignment)
                    <button type="button" class="visit-card clickable-card w-full text-start border-0" wire:click="goToVisit({{ $assignment->id }})">
                        <div class="visit-card-status visit-status-{{ $assignment->status }}"></div>
                        <div class="visit-card-body">
                            <div class="visit-card-top">
                                <strong class="visit-card-name">{{ $assignment->customer?->name_ar ?? '?' }}</strong>
                                <span class="badge @if($assignment->status === 'completed') badge-success @elseif($assignment->status === 'missed') badge-danger @else badge-warning @endif">
                                    {{ $assignment->status === 'completed' ? __('app.done') : ($assignment->status === 'missed' ? __('app.missed') : __('app.pending')) }}
                                </span>
                            </div>
                            <p class="visit-card-address">{{ $assignment->customer?->address }}</p>
                            @if($assignment->customer?->latitude && $assignment->customer?->longitude)
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $assignment->customer->latitude }},{{ $assignment->customer->longitude }}"
                                   target="_blank" rel="noopener" class="maps-link" onclick="event.stopPropagation()">
                                    <x-heroicon-o-map width="14" height="14" />
                                    {{ __('app.directions') }}
                                </a>
                            @endif
                        </div>
                    </button>
                @endforeach
            @endif
        </div>

        {{-- Open Tasks --}}
        @if($openTasks->isNotEmpty())
            <div class="home-section">
                <h3 class="home-section-title">{{ __('app.tasks') }}</h3>
                @foreach($openTasks as $task)
                    <div class="card flex justify-between items-center min-w-0">
                        <div>
                            <strong class="block text-sm">{{ $task->title }}</strong>
                            @if($task->note)
                                <small class="text-text-secondary">{{ $task->note }}</small>
                            @endif
                            @if($task->customer)
                                <small class="block text-text-secondary">{{ $task->customer->name_ar }}</small>
                            @endif
                            @if($task->due_date)
                                <small class="block text-warning text-xs">{{ __('app.due', ['date' => $task->due_date->translatedFormat('j F Y')]) }}</small>
                            @endif
                        </div>
                        <button wire:click="completeTask({{ $task->id }})" class="btn btn-outline text-success text-sm" aria-label="{{ __('app.done') }}">
                            {{ __('app.done') }}
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="home-section">
            <button class="btn btn-primary btn-lg w-full" wire:click="startWork">
                {{ __('app.start_work') }}
            </button>
        </div>

        {{-- Quick Action Buttons --}}
        <div class="home-section">
            <div class="grid grid-cols-3 gap-3">
                <a href="/app/sell" class="quick-action-card">
                    <div class="quick-action-icon">
                        <x-heroicon-o-shopping-cart class="w-6 h-6" />
                    </div>
                    <span class="quick-action-label">{{ __('app.quick_sale') }}</span>
                </a>
                <a href="/app/visits" class="quick-action-card">
                    <div class="quick-action-icon">
                        <x-heroicon-o-map-pin class="w-6 h-6" />
                    </div>
                    <span class="quick-action-label">{{ __('app.log_visit') }}</span>
                </a>
                <a href="/app/stock" class="quick-action-card">
                    <div class="quick-action-icon">
                        <x-heroicon-o-cube class="w-6 h-6" />
                    </div>
                    <span class="quick-action-label">{{ __('app.check_stock') }}</span>
                </a>
            </div>
        </div>
    </div>

    <x-tab-bar active="home" />
</x-pull-to-refresh>
</div>
