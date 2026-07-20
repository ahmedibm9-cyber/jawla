@props(['title' => '', 'message' => '', 'cancelLabel' => null])

{{--
    Consequence-stating confirmation modal (bilingual via the title/message
    props). `trigger` slot = the visible button (type=button); `confirm` slot
    = the real action button, executed only after explicit confirmation.
--}}
@php($modalId = 'modal-'.\Illuminate\Support\Str::random(8))

<div {{ $attributes->merge(['style' => 'overscroll-behavior:contain']) }}>
    <div onclick="document.body.style.overflow='hidden';document.getElementById('{{ $modalId }}').showModal()">{{ $trigger }}</div>

    <dialog id="{{ $modalId }}"
            role="dialog" aria-modal="true" aria-label="{{ $title }}"
            onclose="document.body.style.overflow='';"
            oncancel="document.body.style.overflow='';"
            onclick="if(event.target===this){this.close();document.body.style.overflow='';}"
            style="padding:0;border:none;background:transparent;max-width:none;width:auto;overflow:visible">
        <div class="card" style="max-width:360px;width:calc(100vw - 32px);margin:0 auto">
            <h3 class="m-0 mb-2">{{ $title }}</h3>
            <p class="m-0 mb-4 text-text-secondary">{{ $message }}</p>
            <div class="flex gap-2">
                <button type="button" class="btn btn-outline flex-1"
                        onclick="document.getElementById('{{ $modalId }}').close();document.body.style.overflow='';">
                    {{ $cancelLabel ?? __('app.cancel') }}
                </button>
                <div class="flex-1"
                     onclick="document.getElementById('{{ $modalId }}').close();document.body.style.overflow='';">{{ $confirm }}</div>
            </div>
        </div>
    </dialog>
</div>
