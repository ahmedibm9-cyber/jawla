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
    $wireModel = $attributes->wire('model')->value();

    $normalized = collect($options)->map(function ($o) use ($optionValue, $optionLabel) {
        if (is_array($o)) {
            return ['value' => (string) ($o['value'] ?? $o[$optionValue] ?? ''), 'label' => (string) ($o['label'] ?? $o[$optionLabel] ?? '')];
        }

        return ['value' => (string) $o->{$optionValue}, 'label' => (string) ($o->{$optionLabel} ?? '')];
    })->values();
@endphp

<div
    x-data="{
        open: false,
        search: '',
        highlighted: -1,
        selected: @js($wireModel) ? $wire.entangle(@js($wireModel)) : null,
        options: {{ \Illuminate\Support\Js::from($normalized) }},
        get filtered() {
            if (! this.search) return this.options;
            const q = this.search.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(q));
        },
        labelFor(val) {
            const o = this.options.find(o => String(o.value) === String(val));
            return o ? o.label : '';
        },
        init() {
            this.search = this.labelFor(this.selected);
            this.$watch('selected', v => { if (! this.open) this.search = this.labelFor(v); });
        },
        choose(o) {
            this.selected = o.value;
            this.search = o.label;
            this.open = false;
            this.highlighted = -1;
        },
        closeList() {
            this.open = false;
            this.highlighted = -1;
            this.search = this.labelFor(this.selected);
        },
        move(dir) {
            if (! this.open) { this.open = true; return; }
            const n = this.filtered.length;
            if (n === 0) return;
            this.highlighted = (this.highlighted + dir + n) % n;
        },
        enter() {
            if (this.open && this.highlighted >= 0 && this.filtered[this.highlighted]) {
                this.choose(this.filtered[this.highlighted]);
            }
        }
    }"
    x-on:keydown.escape.prevent.stop="closeList()"
    x-on:click.outside="closeList()"
    class="relative"
>
    <input
        type="text"
        id="{{ $acId }}"
        role="combobox"
        aria-autocomplete="list"
        :aria-expanded="open.toString()"
        aria-controls="{{ $acId }}-listbox"
        :aria-activedescendant="highlighted >= 0 ? '{{ $acId }}-opt-' + highlighted : null"
        autocomplete="off"
        @if($required) aria-required="true" @endif
        placeholder="{{ $placeholder ?? __('app.search') }}"
        {{ $attributes->except(['wire:model'])->merge(['class' => 'form-input w-full']) }}
        x-model="search"
        x-on:focus="open = true"
        x-on:click="open = true"
        x-on:input="open = true; highlighted = -1"
        x-on:keydown.arrow-down.prevent="move(1)"
        x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.enter.prevent="enter()"
    >

    <ul
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        id="{{ $acId }}-listbox"
        role="listbox"
        class="card"
        style="position:absolute;z-index:60;inset-inline:0;margin-top:4px;padding:4px;max-height:240px;overflow-y:auto;overscroll-behavior:contain"
    >
        <template x-for="(o, i) in filtered" :key="o.value">
            <li
                :id="'{{ $acId }}-opt-' + i"
                role="option"
                :aria-selected="(String(o.value) === String(selected)).toString()"
                x-text="o.label"
                x-on:click="choose(o)"
                x-on:mouseenter="highlighted = i"
                :class="{ 'bg-surface-hover': highlighted === i, 'font-semibold': String(o.value) === String(selected) }"
                class="rounded-md cursor-pointer"
                style="padding:10px 12px;text-align:start"
            ></li>
        </template>
        <li
            x-show="filtered.length === 0"
            class="text-text-muted"
            style="padding:10px 12px;text-align:start"
            x-text="@js(__('app.no_results'))"
        ></li>
    </ul>
</div>
