<div class="main-content">
    <div style="background:#4DB848;color:#fff;padding:16px 20px 20px">
        <h2 style="margin:0 0 4px;font-size:1.3rem">{{ __('app.welcome', ['name' => $user->name]) }}</h2>
        <p style="margin:0;opacity:0.85;font-size:0.9rem">{{ __('app.today_visits') }}: {{ $visitCount }}</p>
    </div>

    <div style="padding:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <a href="/app" style="text-decoration:none">
            <div class="card" style="text-align:center;padding:20px 12px">
                <div style="font-size:2rem;font-weight:700;color:#4DB848">{{ $pendingCount }}</div>
                <div style="color:#6b7280;font-size:0.9rem;margin-top:4px">{{ __('app.visits_pending') }}</div>
            </div>
        </a>
        <a href="/app" style="text-decoration:none">
            <div class="card" style="text-align:center;padding:20px 12px">
                <div style="font-size:2rem;font-weight:700;color:#16A34A">{{ $completedCount }}</div>
                <div style="color:#6b7280;font-size:0.9rem;margin-top:4px">{{ __('app.visits_done') }}</div>
            </div>
        </a>
    </div>

    <div style="padding:0 16px 16px">
        <h3 style="margin:0 0 12px">{{ __('app.todays_plan') }}</h3>
        @if($todayVisits->isEmpty())
            <div class="card" style="text-align:center;padding:32px 16px;color:#9ca3af">
                <svg style="width:48px;height:48px;margin-bottom:12px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p style="margin:0;font-size:1rem">{{ __('app.no_visits') }}</p>
            </div>
        @else
            @foreach($todayVisits as $assignment)
                <div class="card" style="cursor:pointer" wire:click="goToVisit({{ $assignment->id }})">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <strong style="display:block">{{ $assignment->customer?->name_ar ?? '?' }}</strong>
                            <small style="color:#6b7280">{{ $assignment->customer?->address }}</small>
                            @if($assignment->customer?->latitude && $assignment->customer?->longitude)
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $assignment->customer->latitude }},{{ $assignment->customer->longitude }}"
                                   target="_blank" class="maps-link" onclick="event.stopPropagation()">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 9m-6-2l6-3m6 10V9m-6 10V9"/></svg>
                                    {{ app()->getLocale() === 'ar' ? 'اتجاهات' : 'Directions' }}
                                </a>
                            @endif
                        </div>
                        <span class="badge @if($assignment->status === 'completed') badge-success @elseif($assignment->status === 'missed') badge-danger @else badge-warning @endif">
                            {{ $assignment->status === 'completed' ? __('app.done') : ($assignment->status === 'missed' ? __('app.missed') : __('app.pending')) }}
                        </span>
                    </div>
                </div>
            @endforeach
        @endif

        <button class="btn btn-primary" style="width:100%;margin-top:12px" wire:click="startWork">
            {{ __('app.start_work') }}
        </button>
    </div>
</div>

{{-- Bottom Tab Bar --}}
<nav class="tab-bar">
    <a href="/app" class="tab-item active">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        {{ __('app.home') }}
    </a>
    <a href="/app/customers" class="tab-item">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        {{ __('app.customers') }}
    </a>
    <a href="/app/stock" class="tab-item">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        {{ __('app.stock') }}
    </a>
    <a href="/app/more" class="tab-item">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        {{ __('app.more') }}
    </a>
</nav>