<div class="main-content">
    <x-page-header :title="__('app.van_transfers')">
        <x-slot:icon><svg fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z'/><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($successMessage)
            <x-ds.toast type="success" :message="$successMessage" />
        @endif
        @if($errorMessage)
            <x-ds.toast type="error" :message="$errorMessage" />
        @endif

        {{-- Incoming --}}
        <h3 class="text-sm font-semibold mb-2">{{ __('app.transfers_incoming') }}</h3>
        @forelse($incoming as $t)
            <div class="card mb-2" wire:key="in-{{ $t->id }}">
                <div class="flex justify-between items-start gap-2">
                    <div class="min-w-0">
                        <strong class="block">{{ __('app.from') }}: {{ $t->fromUser?->name ?? '—' }}</strong>
                        <small class="text-text-muted">{{ $t->created_at->format('Y-m-d H:i') }}</small>
                    </div>
                    <span class="badge {{ $t->status->value === 'shipped' ? 'badge-info' : 'badge-warning' }}">
                        {{ __('app.transfer_status_'.$t->status->value) }}
                    </span>
                </div>
                <ul class="mt-2 text-sm text-text-secondary">
                    @foreach($t->items as $item)
                        <li class="flex justify-between">
                            <span>{{ l($item->product?->name_ar, $item->product?->name_en ?? $item->product?->name_ar) }}</span>
                            <span dir="ltr">× {{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</span>
                        </li>
                    @endforeach
                </ul>
                @if($t->status === \App\Enums\VanTransferStatus::Shipped)
                    <x-ds.modal class="mt-3" :title="__('app.confirm_receive_title')" :message="__('app.confirm_receive_msg')">
                        <x-slot:trigger>
                            <button type="button" class="btn btn-primary w-full">{{ __('app.receive_transfer') }}</button>
                        </x-slot:trigger>
                        <x-slot:confirm>
                            <button type="button" wire:click="receive({{ $t->id }})" wire:loading.attr="disabled" class="btn btn-primary w-full">
                                <span wire:loading.remove>{{ __('app.confirm') }}</span>
                                <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                            </button>
                        </x-slot:confirm>
                    </x-ds.modal>
                @endif
            </div>
        @empty
            <x-ds.empty icon="heroicon-o-inbox" :message="__('app.no_incoming_transfers')" />
        @endforelse

        {{-- Outgoing --}}
        @if($outgoing->isNotEmpty())
            <h3 class="text-sm font-semibold mb-2 mt-6">{{ __('app.transfers_outgoing') }}</h3>
            @foreach($outgoing as $t)
                <div class="card mb-2" wire:key="out-{{ $t->id }}">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <strong class="block">{{ __('app.to') }}: {{ $t->toUser?->name ?? '—' }}</strong>
                            <small class="text-text-muted">{{ $t->created_at->format('Y-m-d H:i') }}</small>
                        </div>
                        <span class="badge badge-neutral">{{ __('app.transfer_status_'.$t->status->value) }}</span>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <x-tab-bar active="more" />
</div>
