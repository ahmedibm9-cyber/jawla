@props(['variant' => 'primary', 'type' => 'button', 'target' => null])

{{--
    Six-state button: default / hover / focus-visible / active / disabled
    come from the .btn CSS; the loading state is driven by wire:loading
    when a `target` (Livewire action name) is given.
--}}
<button type="{{ $type }}"
    {{ $attributes->merge(['class' => 'btn btn-'.$variant]) }}
    @if($target) wire:loading.attr="disabled" wire:target="{{ $target }}" @endif>
    @if($target)
        <span wire:loading.remove wire:target="{{ $target }}">{{ $slot }}</span>
        <span wire:loading wire:target="{{ $target }}">{{ __('app.saving') }}&hellip;</span>
    @else
        {{ $slot }}
    @endif
</button>
