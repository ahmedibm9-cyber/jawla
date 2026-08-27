<div class="main-content">
    <x-page-header :title="__('app.call_history')">
        <x-slot:icon><x-heroicon-o-phone class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($customer)
            <div class="card mb-4">
                <h2 class="font-semibold text-base">{{ l($customer->name_ar, $customer->name_en ?? $customer->name_ar) }}</h2>
            </div>
        @endif

        @forelse($calls as $call)
            <article class="card mb-3" wire:key="call-{{ $call->id }}">
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-base">{{ __('app.call_direction_'.$call->direction) }}</h2>
                        @if($call->contact)
                            <p class="text-sm text-text-secondary mt-1">{{ $call->contact->name }} - {{ $call->contact->phone }}</p>
                        @endif
                    </div>
                    <span class="badge @class([
                        'badge-success' => $call->outcome === 'reached',
                        'badge-danger' => $call->outcome === 'no_answer',
                        'badge-warning' => $call->outcome === 'busy',
                        'badge-info' => $call->outcome === 'left_voicemail',
                    ])">{{ __('app.call_outcome_'.$call->outcome) }}</span>
                </div>

                <div class="flex flex-wrap gap-2 mt-3 text-xs text-text-muted">
                    <span>{{ __('app.call_duration') }}: {{ $call->duration_formatted }}</span>
                    <span>· {{ $call->called_at->format('Y-m-d H:i') }}</span>
                </div>

                @if($call->notes)
                    <p class="text-sm mt-3 whitespace-pre-line">{{ $call->notes }}</p>
                @endif
            </article>
        @empty
            <x-ds.empty icon="heroicon-o-phone" :message="__('app.no_calls')" />
        @endforelse
    </div>

    <x-tab-bar active="more" />
</div>
