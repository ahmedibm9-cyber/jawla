<div class="main-content">
    <x-page-header :title="__('app.agenda')">
        <x-slot:icon><x-heroicon-o-list-bullet class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($showNonPlannedForm)
            <div class="card mb-4">
                <h3 class="font-semibold text-sm mb-3">{{ __('app.start_non_planned_visit') }}</h3>
                <div class="space-y-3">
                    <div>
                        <input type="text" wire:model.live.debounce.300ms="customerSearch"
                               placeholder="{{ __('app.search_customers') }}"
                               class="input input-bordered w-full" />
                        @if(count($customers) > 0)
                            <div class="border rounded-lg mt-1 max-h-40 overflow-y-auto">
                                @foreach($customers as $customer)
                                    <button type="button" wire:click="$set('selectedCustomerId', {{ $customer->id }}); $set('customerSearch', '{{ addslashes($customer->name_ar ?? $customer->name_en) }}')"
                                            class="w-full text-left px-3 py-2 hover:bg-base-200 text-sm">
                                        {{ $customer->name_ar ?? $customer->name_en }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($selectedCustomerId)
                        <input type="text" wire:model="visitPurpose"
                               placeholder="{{ __('app.visit_purpose') }}"
                               class="input input-bordered w-full" />
                        <div class="flex gap-2">
                            <button wire:click="startNonPlannedVisit" class="btn btn-primary btn-sm flex-1">
                                {{ __('app.start_visit') }}
                            </button>
                            <button wire:click="$set('showNonPlannedForm', false)" class="btn btn-ghost btn-sm">
                                {{ __('app.cancel') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <button wire:click="$set('showNonPlannedForm', true)" class="btn btn-outline btn-sm w-full mb-4">
                <x-heroicon-o-plus class="w-4 h-4" />
                {{ __('app.non_planned_visit') }}
            </button>
        @endif

        @forelse($items as $item)
            <article class="card mb-3">
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            @if($item['type'] === 'visit')
                                <x-heroicon-o-map-pin class="w-4 h-4 text-primary" />
                            @elseif($item['type'] === 'todo')
                                <x-heroicon-o-check-circle class="w-4 h-4 text-warning" />
                            @elseif($item['type'] === 'invoice')
                                <x-heroicon-o-document-text class="w-4 h-4 text-success" />
                            @elseif($item['type'] === 'return')
                                <x-heroicon-o-arrow-uturn-left class="w-4 h-4 text-danger" />
                            @endif
                            <h4 class="font-medium text-sm">{{ $item['title'] }}</h4>
                        </div>
                        @if($item['description'])
                            <p class="text-sm text-text-secondary mt-1">{{ $item['description'] }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-text-muted">{{ $item['time'] }}</span>
                        <span class="badge @class([
                            'badge-success' => $item['status'] === 'completed' || $item['status'] === 'approved',
                            'badge-danger' => $item['status'] === 'cancelled',
                            'badge-warning' => $item['status'] === 'in_progress' || $item['status'] === 'pending',
                            'badge-info' => $item['status'] === 'new' || $item['status'] === 'open',
                            'badge-neutral' => ! in_array($item['status'], ['completed', 'approved', 'cancelled', 'in_progress', 'pending', 'new', 'open'], true),
                        ]) mt-1 inline-block">{{ $item['status'] }}</span>
                    </div>
                </div>
                @if(isset($item['amount']))
                    <p class="text-sm font-semibold mt-2">{{ number_format($item['amount'], 2) }}</p>
                @endif
                @if(isset($item['priority']))
                    <span class="badge @class([
                        'badge-danger' => $item['priority'] === 'high',
                        'badge-warning' => $item['priority'] === 'medium',
                        'badge-neutral' => $item['priority'] === 'low',
                    ]) mt-2 inline-block">{{ $item['priority'] }}</span>
                @endif
            </article>
        @empty
            <x-ds.empty icon="heroicon-o-list-bullet" :message="__('app.no_agenda_items')" />
        @endforelse
    </div>

    <x-tab-bar active="more" />
</div>
