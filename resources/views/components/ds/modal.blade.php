@props(['title' => '', 'message' => '', 'cancelLabel' => null])

{{--
    Consequence-stating confirmation modal (bilingual via the title/message
    props). `trigger` slot = the visible button (type=button); `confirm` slot
    = the real action button, executed only after explicit confirmation.
--}}
<div x-data="{ open: false }" {{ $attributes->merge(['style' => 'overscroll-behavior:contain']) }}>
    <div x-on:click="open = true">{{ $trigger }}</div>

    <div x-show="open" x-cloak x-trap.noscroll="open"
         style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);padding:16px"
         role="dialog" aria-modal="true" aria-label="{{ $title }}"
         x-on:keydown.escape.window="open = false">
        <div class="card" style="max-width:360px;width:100%;margin:0" x-on:click.outside="open = false">
            <h3 class="m-0 mb-2">{{ $title }}</h3>
            <p class="m-0 mb-4 text-text-secondary">{{ $message }}</p>
            <div class="flex gap-2">
                <button type="button" class="btn btn-outline flex-1" x-on:click="open = false">
                    {{ $cancelLabel ?? __('app.cancel') }}
                </button>
                <div class="flex-1" x-on:click="open = false">{{ $confirm }}</div>
            </div>
        </div>
    </div>
</div>
