<div
  x-data="{
    open: false,
    search: '',
    selected: {{ $selected ?? 'null' }},
    options: {{ Js::from($options) }},
    get filtered() {
      const q = this.search.toLowerCase().trim();
      if (!q) return this.options;
      return this.options.filter(o =>
        o.label.toLowerCase().includes(q)
      ).slice(0, 20);
    },
    select(opt) {
      this.selected = opt;
      this.search = '';
      this.open = false;
      $refs.input.value = opt.value;
      $refs.input.dispatchEvent(new Event('input', { bubbles: true }));
    },
    clear() {
      this.selected = null;
      this.search = '';
      $refs.input.value = '';
      $refs.input.dispatchEvent(new Event('input', { bubbles: true }));
    },
    clickOutside() {
      this.open = false;
    }
  }"
  x-on:click-outside.window="clickOutside()"
  class="relative"
>
  <label for="{{ $id ?? '' }}" class="form-label">{{ $label }}</label>
  <div class="relative">
    <input
      type="text"
      x-model="search"
      x-on:focus="open = true"
      x-on:input="open = true"
      placeholder="{{ $placeholder ?? '' }}"
      class="form-input"
      autocomplete="off"
      role="combobox"
      aria-expanded="open"
      aria-haspopup="listbox"
    >
    <template x-if="selected">
      <div class="absolute inset-0 flex items-center px-3 pointer-events-none">
        <span x-text="selected.label" class="text-sm"></span>
      </div>
    </template>
    <button
      type="button"
      x-show="selected"
      x-on:click="clear()"
      class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
      aria-label="{{ __('app.clear') }}"
    >&times;</button>
  </div>

  <input type="hidden" x-ref="input" name="{{ $name }}" value="{{ $value ?? '' }}">

  <div
    x-show="open && filtered.length > 0"
    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
    role="listbox"
  >
    <template x-for="opt in filtered" :key="opt.value">
      <div
        x-on:click="select(opt)"
        x-on:keydown.enter="select(opt)"
        class="px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm"
        :class="{'bg-green-50': selected && selected.value === opt.value}"
        role="option"
        tabindex="0"
      >
        <span x-text="opt.label"></span>
        <span x-show="opt.sub" class="text-gray-400 text-xs ml-2" x-text="opt.sub"></span>
      </div>
    </template>
  </div>

  <div
    x-show="open && filtered.length === 0 && search.length > 0"
    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-3 text-gray-400 text-sm"
  >
    {{ $emptyText ?? 'No results' }}
  </div>
</div>
