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

<div id="{{ $acId }}-root" class="relative">
    <input type="hidden" id="{{ $acId }}-hidden" {{ $wireModelAttributes }}>
    <input
        type="text"
        id="{{ $acId }}"
        role="combobox"
        aria-autocomplete="list"
        aria-expanded="false"
        aria-controls="{{ $acId }}-listbox"
        autocomplete="off"
        @if($required) aria-required="true" @endif
        placeholder="{{ $placeholder ?? __('app.search') }}"
        {{ $passthroughAttributes->merge(['class' => 'form-input w-full']) }}
        list="{{ $acId }}-listbox"
        oninput="window.jawlaAutocompleteSync && window.jawlaAutocompleteSync('{{ $acId }}')"
        onchange="window.jawlaAutocompleteSync && window.jawlaAutocompleteSync('{{ $acId }}')"
        onblur="window.jawlaAutocompleteFinalize && window.jawlaAutocompleteFinalize('{{ $acId }}')"
    >

    <datalist id="{{ $acId }}-listbox">
        @foreach($normalized as $option)
            <option value="{{ $option['display'] }}"></option>
        @endforeach
    </datalist>

    <script>
        window.jawlaAutocompleteRegistry = window.jawlaAutocompleteRegistry || {};
        window.jawlaAutocompleteRegistry[@js($acId)] = @json($normalized);
        const jawlaInit{{ str_replace('-', '_', $acId) }} = () => {
            if (window.jawlaAutocompleteInit) {
                window.jawlaAutocompleteInit(@js($acId));
            }
        };

        if (window.jawlaAutocompleteInit) {
            jawlaInit{{ str_replace('-', '_', $acId) }}();
        } else {
            window.addEventListener('load', jawlaInit{{ str_replace('-', '_', $acId) }}, { once: true });
        }
    </script>
</div>
