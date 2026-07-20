@props([
    'payload' => [],
    'label' => null,
])

<div x-data="jawlaBluetoothPrinter({ payload: @js($payload) })" class="w-full">
    <button
        type="button"
        class="btn btn-outline w-full"
        x-on:click="printDocument()"
        x-bind:disabled="busy"
    >
        <span x-show="!busy">{{ $label ?? __('app.print_bluetooth') }}</span>
        <span x-show="busy">{{ __('app.printing') }}&hellip;</span>
    </button>

    <div class="flex gap-2 mt-2" x-show="supported">
        <button type="button" class="btn flex-1 text-sm" x-on:click="pairPrinter()">{{ __('app.pair_printer') }}</button>
        <button type="button" class="btn flex-1 text-sm" x-show="hasSavedPrinter" x-on:click="forgetPrinter()">{{ __('app.change_printer') }}</button>
    </div>

    <small class="block mt-2 text-text-secondary" aria-live="polite" x-text="message"></small>
</div>
