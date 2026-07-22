<div>
<x-pull-to-refresh>
<div class="main-content">
    <x-page-header :title="__('app.visits')" />

    <div class="page-body">
        <div wire:loading.delay class="space-y-2 mb-3" aria-hidden="true">
            <x-ds.skeleton height="72px" />
            <x-ds.skeleton height="72px" />
        </div>

        @if($visits->isEmpty())
            <x-ds.empty icon="heroicon-o-map-pin" :message="__('app.no_visits_yet')">
                <x-slot:action>
                    <a href="/app" class="btn btn-primary no-underline">{{ __('app.back_home') }}</a>
                </x-slot:action>
            </x-ds.empty>
        @else
            @php $lastDate = null; @endphp
            @foreach($visits as $visit)
                @php $date = $visit->created_at->translatedFormat('l j F Y'); @endphp
                @if($date !== $lastDate)
                    <h3 class="home-section-title mt-4 mb-2">{{ $visit->created_at->isToday() ? __('app.today') : $date }}</h3>
                    @php $lastDate = $date; @endphp
                @endif

                <a href="{{ route('app.visit', $visit) }}" class="card block no-underline text-inherit mb-2">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <strong class="block truncate">{{ $visit->customer?->name_ar }}</strong>
                            <small class="text-text-secondary">{{ $visit->created_at->format('H:i') }}</small>
                        </div>
                        @if($visit->status?->value === 'closed')
                            <span class="badge badge-success shrink-0">{{ __('app.visits_done') }}</span>
                        @elseif($visit->arrival_confirmed)
                            <span class="badge badge-info shrink-0">{{ __('app.arrived') }}</span>
                        @else
                            <span class="badge badge-warning shrink-0">{{ __('app.visits_pending') }}</span>
                        @endif
                    </div>
                </a>
            @endforeach

            <div class="mt-4">
                {{ $visits->links() }}
            </div>
        @endif
    </div>
</div>

<x-tab-bar active="visits" />
</x-pull-to-refresh>
</div>
