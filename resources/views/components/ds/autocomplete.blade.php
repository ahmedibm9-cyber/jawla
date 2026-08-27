@props([
    'options' => [],          // iterable of models or ['value'=>, 'label'=>] arrays
    'optionValue' => 'id',    // model attribute used as the value
    'optionLabel' => 'name_ar', // model attribute used as the visible label
    'placeholder' => null,
    'id' => null,
    'required' => false,
])

{{--
    Searchable single-select. Filters the already-loaded `options` client-side
    (no server round-trip) and binds the chosen value to a Livewire property via
    the caller's `wire:model`. RTL-aware, ARIA combobox, full keyboard support.
--}}
@php
    $acId = $id ?: 'ac-'.\Illuminate\Support\Str::random(6);
    $wireModelAttributes = $attributes->whereStartsWith('wire:model');
    $passthroughAttributes = $attributes->except(array_keys($wireModelAttributes->getAttributes()));

    $normalized = collect($options)->map(function ($o) use ($optionValue, $optionLabel) {
        if (is_array($o)) {
            $label = (string) ($o['label'] ?? $o[$optionLabel] ?? '');
            $hint = (string) ($o['hint'] ?? $o['code'] ?? $o['sku'] ?? $o['phone'] ?? '');

            return ['value' => (string) ($o['value'] ?? $o[$optionValue] ?? ''), 'label' => $label, 'hint' => $hint];
        }

        $label = (string) ($o->{$optionLabel} ?? '');
        $hint = (string) ($o->code ?? $o->sku ?? $o->phone ?? '');

        return ['value' => (string) $o->{$optionValue}, 'label' => $label, 'hint' => $hint];
    });

    $labelCounts = $normalized->countBy('label');
    $normalized = $normalized->map(function ($option) use ($labelCounts) {
        $display = $option['label'];

        if (($labelCounts[$option['label']] ?? 0) > 1) {
            $display .= $option['hint'] !== ''
                ? ' — '.$option['hint']
                : ' — #'.$option['value'];
        }

        $option['display'] = $display;

        return $option;
    })->values();
@endphp

<div id="{{ $acId }}-root" class="relative"
    x-data="jawlaAutocomplete({ options: @js($normalized), noResultsText: @js(__('app.no_results')) })"
    x-init="init()"
    x-on:click.outside="closeList()">
    <input type="hidden" id="{{ $acId }}-hidden" x-ref="hidden" {{ $wireModelAttributes }}>
    <input
        type="text"
        id="{{ $acId }}"
        role="combobox"
        aria-autocomplete="list"
        x-bind:aria-expanded="open.toString()"
        aria-controls="{{ $acId }}-listbox"
        x-bind:aria-activedescendant="highlighted >= 0 ? '{{ $acId }}-option-' + highlighted : null"
        autocomplete="off"
        @if($required) aria-required="true" @endif
        placeholder="{{ $placeholder ?? __('app.search') }}"
        {{ $passthroughAttributes->merge(['class' => 'form-input w-full']) }}
        x-model="search"
        x-on:focus="open = true"
        x-on:input="open = true; highlighted = -1; if (!search) clear()"
        x-on:keydown.arrow-down.prevent="move(1)"
        x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.enter.prevent="enter()"
        x-on:keydown.escape.prevent="closeList()"
    >

    <ul id="{{ $acId }}-listbox" role="listbox" x-cloak
        x-show="open && filtered.length > 0"
        class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg shadow-lg"
        style="background: var(--jj-surface); border: 1px solid var(--jj-border)">
        <template x-for="(option, index) in filtered" :key="option.value">
            <li role="option"
                x-bind:id="'{{ $acId }}-option-' + index"
                x-bind:aria-selected="String(selected) === String(option.value)"
                x-bind:class="{ 'font-semibold': String(selected) === String(option.value), 'bg-surface-hover': highlighted === index }"
                class="cursor-pointer px-3 py-2 text-sm"
                x-on:mousedown.prevent="choose(option)"
                x-on:mouseenter="highlighted = index">
                <span x-text="option.display || option.label"></span>
            </li>
        </template>
    </ul>

    <p class="sr-only" aria-live="polite" x-text="open && search && filtered.length === 0 ? noResultsText : ''"></p>
</div>
