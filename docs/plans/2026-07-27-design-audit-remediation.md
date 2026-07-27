# Design Audit Remediation Plan (8.5 → 9.5+/10)

> **For Claude:** Use `@skills/writing-plans/SKILL.md` to implement this plan task-by-task.

**Goal:** Fix all 16 audit findings to push the Rep PWA design score from 8.5/10 to 9.5+/10.

**Architecture:** Targeted fixes only — no new components, no refactors. Add missing CSS class, add missing i18n keys, fix hardcoded strings, fix dark mode gaps, fix dead code. Each task is one file or one concern.

**Tech Stack:** Blade templates, Tailwind CSS (utility classes), custom CSS (`app.php` lang files), Livewire.

---

## Task 1: Add missing `.alert` CSS class

**Files:**

- Modify: `resources/css/app.css:1290` (after `.form-error`)

**Step 1: Add `.alert` and `.alert-danger` rules**

Insert after line 1290 (after `.form-error` closing brace):

```css
/* ---- Alert ---- */
.alert {
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 12px;
}
.alert-danger {
  background: var(--color-danger-bg, #fef2f2);
  color: var(--color-danger);
  border: 1px solid var(--color-danger-border, #fecaca);
}
```

**Step 2: Verify**

Run: `make build` — no CSS errors.

**Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "fix: add missing .alert CSS class for error messages"
```

---

## Task 2: Add missing i18n keys to lang files

**Files:**

- Modify: `lang/en/app.php:355` (before closing `];`)
- Modify: `lang/ar/app.php:355` (before closing `];`)

**Step 1: Add English keys**

Insert before the closing `];` in `lang/en/app.php`:

```php
    'collect_another' => 'Collect Another Payment',
    'original_invoice' => 'Original Invoice',
    'invoice_line' => 'Invoice Line',
    'condition' => 'Condition',
    'sellable' => 'Sellable',
    'damaged_quarantine' => 'Damaged — quarantine',
    'search_customer_ph' => 'Search customer…',
    'search_product_ph' => 'Search by SKU or name…',
```

**Step 2: Add Arabic keys**

Insert before the closing `];` in `lang/ar/app.php`:

```php
    'collect_another' => 'تحصيل آخر',
    'original_invoice' => 'الفاتورة الأصلية',
    'invoice_line' => 'بند الفاتورة',
    'condition' => 'الحالة',
    'sellable' => 'صالح للبيع',
    'damaged_quarantine' => 'تالف — إلى الحجر',
    'search_customer_ph' => 'ابحث عن عميل…',
    'search_product_ph' => 'ابحث بالكود أو الاسم…',
```

**Step 3: Verify**

Run: `php artisan tinker --execute="dump(__('app.collect_another')); dump(__('app.original_invoice'));"` — should print translated strings.

**Step 4: Commit**

```bash
git add lang/en/app.php lang/ar/app.php
git commit -m "feat: add missing i18n keys for returns, payments, placeholders"
```

---

## Task 3: Fix hardcoded strings in `log-return.blade.php`

**Files:**

- Modify: `resources/views/livewire/app/log-return.blade.php:3,17,33,53,69,71,72`

**Step 1: Replace inline SVG in page-header with heroicon**

Line 3 — replace the inline SVG `<x-slot:icon>` with:

```blade
        <x-slot:icon><x-heroicon-o-arrow-uturn-left width="22" height="22" /></x-slot:icon>
```

**Step 2: Replace hardcoded error div with styled alert**

Line 17 — replace:

```blade
                <div class="alert alert-danger" role="alert">{{ $errorMessage }}</div>
```

With:

```blade
                <div class="alert alert-danger" role="alert">{{ $errorMessage }}</div>
```

(No change needed — the `.alert` CSS from Task 1 now styles it.)

**Step 3: Replace hardcoded locale checks with `__()` calls**

Line 33 — replace:

```blade
                    <label for="against_invoice_id" class="form-label">{{ app()->getLocale() === 'ar' ? 'الفاتورة الأصلية' : 'Original invoice' }} *</label>
```

With:

```blade
                    <label for="against_invoice_id" class="form-label">{{ __('app.original_invoice') }} *</label>
```

Line 53 — replace:

```blade
                                <label for="item_{{ $index }}_line" class="form-label">{{ app()->getLocale() === 'ar' ? 'بند الفاتورة' : 'Invoice line' }}</label>
```

With:

```blade
                                <label for="item_{{ $index }}_line" class="form-label">{{ __('app.invoice_line') }}</label>
```

Line 69 — replace:

```blade
                                    <label for="item_{{ $index }}_condition" class="form-label">{{ app()->getLocale() === 'ar' ? 'الحالة' : 'Condition' }}</label>
```

With:

```blade
                                    <label for="item_{{ $index }}_condition" class="form-label">{{ __('app.condition') }}</label>
```

Line 71 — replace:

```blade
                                        <option value="sellable">{{ app()->getLocale() === 'ar' ? 'صالح للبيع' : 'Sellable' }}</option>
```

With:

```blade
                                        <option value="sellable">{{ __('app.sellable') }}</option>
```

Line 72 — replace:

```blade
                                        <option value="damaged">{{ app()->getLocale() === 'ar' ? 'تالف — إلى الحجر' : 'Damaged — quarantine' }}</option>
```

With:

```blade
                                        <option value="damaged">{{ __('app.damaged_quarantine') }}</option>
```

**Step 4: Add `aria-describedby` to return item fields**

After each `@error` block in the return items section, add error ids. Specifically:

Line 66 — after the quantity input, add:

```blade
                    @error('items.'.$index.'.quantity') <small id="item_{{ $index }}_qty-error" class="form-error">{{ $message }}</small> @enderror
```

**Step 5: Verify**

Run: `make build` — no errors. Manually verify Arabic/English toggle shows correct labels.

**Step 6: Commit**

```bash
git add resources/views/livewire/app/log-return.blade.php
git commit -m "fix: replace hardcoded locale checks with i18n keys in log-return"
```

---

## Task 4: Fix `van-transfers.blade.php` dark mode + loading + error toast

**Files:**

- Modify: `resources/views/livewire/app/van-transfers.blade.php:12,24,31,42,61`

**Step 1: Replace error toast with `<x-ds.toast>` pattern**

Line 12 — replace:

```blade
            <div class="toast toast-error relative top-0 mb-4" role="alert" style="transform:none">{{ $errorMessage }}</div>
```

With:

```blade
            <x-ds.toast type="error" :message="$errorMessage" />
```

**Step 2: Add dark mode to shipped badge**

Line 24 — replace:

```blade
                    <span class="badge {{ $t->status->value === 'shipped' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
```

With:

```blade
                    <span class="badge {{ $t->status->value === 'shipped' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
```

**Step 3: Add dark mode to outgoing badge**

Line 61 — replace:

```blade
                        <span class="badge bg-gray-100 text-gray-700">{{ __('app.transfer_status_'.$t->status->value) }}</span>
```

With:

```blade
                        <span class="badge bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300">{{ __('app.transfer_status_'.$t->status->value) }}</span>
```

**Step 4: Add loading text to receive button**

Line 42 — replace:

```blade
                            <button type="button" wire:click="receive({{ $t->id }})" wire:loading.attr="disabled" class="btn btn-primary w-full">{{ __('app.confirm') }}</button>
```

With:

```blade
                            <button type="button" wire:click="receive({{ $t->id }})" wire:loading.attr="disabled" class="btn btn-primary w-full">
                                <span wire:loading.remove>{{ __('app.confirm') }}</span>
                                <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                            </button>
```

**Step 5: Replace hardcoded product name locale check**

Line 31 — replace:

```blade
                            <span>{{ $ar ? $item->product?->name_ar : ($item->product?->name_en ?? $item->product?->name_ar) }}</span>
```

With:

```blade
                            <span>{{ app()->getLocale() === 'ar' ? $item->product?->name_ar : ($item->product?->name_en ?? $item->product?->name_ar) }}</span>
```

Also remove line 1 (`@php($ar = app()->getLocale() === 'ar')`) since `$ar` is no longer used.

**Step 6: Verify**

Run: `make build` — no errors.

**Step 7: Commit**

```bash
git add resources/views/livewire/app/van-transfers.blade.php
git commit -m "fix: dark mode badges, loading states, toast pattern in van-transfers"
```

---

## Task 5: Fix `collect-payment.blade.php` dead code

**Files:**

- Modify: `resources/views/livewire/app/collect-payment.blade.php:19`

**Step 1: Remove dead `??` fallback**

Line 19 — replace:

```blade
                    <button class="btn btn-outline" wire:click="$set('success', false)">{{ __('app.collect_another') ?? __('app.collect_payment') }}</button>
```

With:

```blade
                    <button class="btn btn-outline" wire:click="$set('success', false)">{{ __('app.collect_another') }}</button>
```

**Step 2: Commit**

```bash
git add resources/views/livewire/app/collect-payment.blade.php
git commit -m "fix: remove dead ?? fallback in collect-payment"
```

---

## Task 6: Fix hardcoded placeholders in `sales-flow.blade.php`

**Files:**

- Modify: `resources/views/livewire/app/sales-flow.blade.php:39`

**Step 1: Replace hardcoded placeholder**

Line 39 — replace:

```blade
                        placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن عميل…' : 'Search customer…' }}">
```

With:

```blade
                        placeholder="{{ __('app.search_customer_ph') }}">
```

**Step 2: Commit**

```bash
git add resources/views/livewire/app/sales-flow.blade.php
git commit -m "fix: replace hardcoded placeholder in sales-flow"
```

---

## Task 7: Fix hardcoded placeholder in `stock-search.blade.php`

**Files:**

- Modify: `resources/views/livewire/app/stock-search.blade.php:10`

**Step 1: Replace hardcoded placeholder**

Line 10 — replace:

```blade
                placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث بالكود أو الاسم…' : 'Search by SKU or name…' }}">
```

With:

```blade
                placeholder="{{ __('app.search_product_ph') }}">
```

**Step 2: Commit**

```bash
git add resources/views/livewire/app/stock-search.blade.php
git commit -m "fix: replace hardcoded placeholder in stock-search"
```

---

## Task 8: Fix hardcoded `rgba` in success-checkmark

**Files:**

- Modify: `resources/css/app.css:1321`

**Step 1: Replace hardcoded rgba with token-based color**

Line 1321 — replace:

```css
box-shadow: 0 4px 16px rgba(109, 184, 59, 0.3);
```

With:

```css
box-shadow: 0 4px 16px color-mix(in srgb, var(--color-success) 30%, transparent);
```

**Step 2: Commit**

```bash
git add resources/css/app.css
git commit -m "fix: use token for success-checkmark box-shadow"
```

---

## Task 9: Standardize dismiss button pattern

**Files:**

- Modify: `resources/css/app.css:2238` (after `.more-logout-btn` rules)
- Modify: `resources/views/livewire/app/home.blade.php:8,14`
- Modify: `resources/views/livewire/app/visit-flow.blade.php:111`
- Modify: `resources/views/livewire/app/quotation-flow.blade.php:12`

**Step 1: Add `.btn-ghost-close` CSS class**

Insert after line 2238 in `app.css`:

```css
/* ---- Ghost close button (dismiss / clear) ---- */
.btn-ghost-close {
  background: transparent;
  border: 0;
  color: inherit;
  cursor: pointer;
  font-size: 1.25rem;
  line-height: 1;
  padding: 4px 8px;
  min-height: 44px;
  min-width: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
```

**Step 2: Replace inline dismiss buttons in `home.blade.php`**

Lines 8 and 14 — replace both instances of:

```blade
class="text-success bg-transparent border-0 cursor-pointer text-lg px-2"
```

and

```blade
class="text-danger bg-transparent border-0 cursor-pointer text-lg px-2"
```

With:

```blade
class="btn-ghost-close"
```

**Step 3: Replace inline dismiss in `visit-flow.blade.php`**

Line 111 — replace:

```blade
                <button type="button" wire:click="$set('errorMessage', '')" aria-label="{{ __('app.clear') }}" class="text-danger bg-transparent border-0 cursor-pointer text-lg px-2">&times;</button>
```

With:

```blade
                <button type="button" wire:click="$set('errorMessage', '')" aria-label="{{ __('app.clear') }}" class="btn-ghost-close">&times;</button>
```

**Step 4: Replace inline dismiss in `quotation-flow.blade.php`**

Line 12 — replace:

```blade
            <button type="button" wire:click="$set('errorMessage', '')" aria-label="{{ __('app.clear') }}" class="text-danger bg-transparent border-0 cursor-pointer text-lg px-2">&times;</button>
```

With:

```blade
            <button type="button" wire:click="$set('errorMessage', '')" aria-label="{{ __('app.clear') }}" class="btn-ghost-close">&times;</button>
```

**Step 5: Verify**

Run: `make build` — no errors.

**Step 6: Commit**

```bash
git add resources/css/app.css resources/views/livewire/app/home.blade.php resources/views/livewire/app/visit-flow.blade.php resources/views/livewire/app/quotation-flow.blade.php
git commit -m "fix: extract dismiss button to .btn-ghost-close class"
```

---

## Task 10: Run full verification

**Step 1: Build assets**

Run: `make build`
Expected: Clean build, no errors.

**Step 2: Lint**

Run: `make lint`
Expected: No new violations.

**Step 3: Typecheck**

Run: `make typecheck`
Expected: No errors.

**Step 4: Commit any fixes from verification**

```bash
git add -A && git commit -m "fix: audit remediation cleanup"
```

---

## Summary of Changes

| Task | Files Changed                                                                   | Finding #       | Impact                                       |
| ---- | ------------------------------------------------------------------------------- | --------------- | -------------------------------------------- |
| 1    | `app.css`                                                                       | #2              | `.alert` error messages now visible          |
| 2    | `lang/en/app.php`, `lang/ar/app.php`                                            | #1, #3          | 8 missing i18n keys added                    |
| 3    | `log-return.blade.php`                                                          | #1, #6, #9      | 5 hardcoded strings → `__()`, heroicon, aria |
| 4    | `van-transfers.blade.php`                                                       | #4, #5, #7, #11 | Dark mode, loading, toast, locale            |
| 5    | `collect-payment.blade.php`                                                     | #3              | Dead `??` fallback removed                   |
| 6    | `sales-flow.blade.php`                                                          | #12             | Hardcoded placeholder → `__()`               |
| 7    | `stock-search.blade.php`                                                        | #13             | Hardcoded placeholder → `__()`               |
| 8    | `app.css`                                                                       | #8              | Token-based success shadow                   |
| 9    | `app.css`, `home.blade.php`, `visit-flow.blade.php`, `quotation-flow.blade.php` | #10             | Dismiss button extracted                     |
| 10   | —                                                                               | —               | Full verification                            |

**Total files modified:** 10
**Estimated effort:** ~30 minutes
