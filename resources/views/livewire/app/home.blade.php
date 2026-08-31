<div>
<x-pull-to-refresh>
    <div class="main-content">
        {{-- Hero Header --}}
        @if($successMessage)
            <div class="toast toast-success relative top-0 mb-4" role="status" style="transform:none">
                <span>{{ $successMessage }}</span>
                <button type="button" wire:click="$set('successMessage', '')" aria-label="{{ __('app.clear') }}" class="btn-ghost-close">&times;</button>
            </div>
        @endif
        @if($errorMessage)
            <div class="toast toast-error relative top-0 mb-4" role="alert" style="transform:none">
                <span>{{ $errorMessage }}</span>
                <button type="button" wire:click="$set('errorMessage', '')" aria-label="{{ __('app.clear') }}" class="btn-ghost-close">&times;</button>
            </div>
        @endif
        <div class="home-hero">
            <div class="home-hero-content">
                <div class="home-hero-brand">
                    <img src="/images/white-j.webp" alt="Jawla" class="home-hero-logo" width="29" height="33">
                </div>
                <div>
                    <h1 class="home-hero-title">{{ __('app.welcome', ['name' => $user->name]) }}</h1>
                    <p class="home-hero-subtitle">
                        {{ $pendingCount }} {{ __('app.visits_pending') }} · {{ $completedCount }} {{ __('app.visits_done') }}
                    </p>
                </div>
            </div>
            {{-- Sync Status Indicator --}}
            <div class="sync-status" wire:click="refreshSyncStatus" role="status" aria-label="{{ $syncStatus['label'] }}">
                <span class="sync-dot sync-dot-{{ $syncStatus['status'] }}"></span>
                <span class="sync-label">{{ $syncStatus['label'] }}</span>
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
                    <button type="button" wire:key="{{ $assignment->id }}" class="visit-card clickable-card w-full text-start border-0" wire:click="goToVisit({{ $assignment->id }})">
                        <div class="visit-card-status visit-status-{{ $assignment->status }}"></div>
                        <div class="visit-card-body">
                            <div class="visit-card-top">
                                <strong class="visit-card-name">{{ $assignment->customer?->name_ar ?? '?' }}</strong>
                                <span class="badge {{ $assignment->status === 'completed' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $assignment->status === 'completed' ? __('app.done') : __('app.pending') }}
                                </span>
                            </div>
                            <p class="visit-card-address">{{ $assignment->customer?->address }}</p>
                            @if($assignment->customer?->latitude && $assignment->customer?->longitude)
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $assignment->customer->latitude }},{{ $assignment->customer->longitude }}"
                                   target="_blank" rel="noopener" class="maps-link" onclick="event.stopPropagation()">
                                    <x-heroicon-o-map width="14" height="14" aria-hidden="true" />
                                    {{ __('app.directions') }}
                                </a>
                            @endif
                        </div>
                    </button>
                @endforeach
            @endif
        </div>

        {{-- Tasks Badge --}}
        @if($openTasks->isNotEmpty())
            <div class="home-section">
                <a class="tasks-badge" href="/app/tasks">
                    <x-heroicon-o-clock class="w-5 h-5" />
                    <span>{{ $openTasks->count() }} {{ __('app.tasks') }}</span>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                </a>
            </div>
        @endif

        {{-- Start Work --}}
        <div class="home-section">
            <button class="btn btn-primary btn-lg w-full" wire:click="startWork">
                {{ __('app.start_day') }}
            </button>
        </div>

        {{-- Quick Actions --}}
        <div class="home-section">
            <div class="quick-actions-row">
                <a href="/app/sell" class="quick-action-pill">
                    <x-heroicon-o-plus class="w-5 h-5" />
                    <span>{{ __('app.new_invoice') }}</span>
                </a>
                <a href="/app/visits" class="quick-action-pill">
                    <x-heroicon-o-map-pin class="w-5 h-5" />
                    <span>{{ __('app.check_in') }}</span>
                </a>
            </div>
        </div>
    </div>

    <x-tab-bar active="home" />
</x-pull-to-refresh>
</div>
