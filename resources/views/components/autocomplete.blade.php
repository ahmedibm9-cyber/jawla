<div
  x-data="{
    open: false,
    search: '',
    selectedId: $el.querySelector('select')?.value || '',
    get filtered() {
      const q = this.search.toLowerCase().trim();
      const opts = Array.from(this.$el.querySelectorAll('select option')).filter(o => o.value);
      if (!q) return opts.map(o => ({value: o.value, label: o.textContent}));
      return opts.filter(o => o.textContent.toLowerCase().includes(q))
                 .map(o => ({value: o.value, label: o.textContent}))
                 .slice(0, 20);
    },
    select(val, label) {
      this.selectedId = val;
      this.search = '';
      this.open = false;
      const sel = this.$el.querySelector('select');
      sel.value = val;
      sel.dispatchEvent(new Event('change', { bubbles: true }));
      sel.dispatchEvent(new Event('input', { bubbles: true }));
      const hidden = this.$el.querySelector('input[type=hidden]');
      if (hidden) hidden.value = val;
    },
    clear() {
      this.selectedId = '';
      this.search = '';
      const sel = this.$el.querySelector('select');
      sel.value = '';
      sel.dispatchEvent(new Event('change', { bubbles: true }));
      sel.dispatchEvent(new Event('input', { bubbles: true }));
      const hidden = this.$el.querySelector('input[type=hidden]');
      if (hidden) hidden.value = '';
    },
    selectedLabel() {
      const sel = this.$el.querySelector('select');
      const opt = sel.options[sel.selectedIndex];
      return opt && opt.value ? opt.textContent : '';
    }
  }"
  x-on:click-outside.window="open = false"
  class="relative"
>
  <label for="{{ $id ?? '' }}" class="form-label">{{ $label }}</label>
  <div class="relative">
    <input
      type="text"
      x-model="search"
      x-on:focus="open = true; if (!search) search = selectedLabel()"
      x-on:input="open = true"
      x-on:keydown.escape="open = false; search = selectedLabel()"
      placeholder="{{ $placeholder ?? '' }}"
      class="form-input"
      autocomplete="off"
      role="combobox"
      aria-expanded="open"
      aria-haspopup="listbox"
    >
    <button
      type="button"
      x-show="selectedId"
      x-on:click="clear()"
      class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
      aria-label="{{ __('app.clear') }}"
    >&times;</button>
  </div>

  <div
    x-show="open && filtered.length > 0"
    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto"
    role="listbox"
  >
    <template x-for="opt in filtered" :key="opt.value">
      <div
        x-on:click="select(opt.value, opt.label)"
        x-on:keydown.enter="select(opt.value, opt.label)"
        class="px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm"
        :class="{'bg-green-50': selectedId === opt.value}"
        role="option"
        tabindex="0"
      >
        <span x-text="opt.label"></span>
      </div>
    </template>
  </div>

  <div
    x-show="open && filtered.length === 0 && search.length > 0"
    class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-3 text-gray-400 text-sm"
  >
    {{ $emptyText ?? 'No results' }}
  </div>

  <input type="hidden" x-ref="input" id="{{ $id ?? '' }}-hidden" name="{{ $name ?? $id ?? '' }}" value="{{ $value ?? '' }}">

  <select style="display:none" {{ $attributes }}>
    {{ $slot }}
  </select>
</div>
