@php($isAr = app()->getLocale() === 'ar')
<div aria-live="polite">
    @if($message)
        <div class="action-toast" role="status"
            x-data x-init="setTimeout(() => $wire.dismiss(), 3500)">
            <span class="min-w-0 truncate">{{ $message }}</span>
            <button type="button" wire:click="dismiss"
                class="action-toast-close" aria-label="{{ $isAr ? 'إغلاق' : 'Dismiss' }}">&times;</button>
        </div>
    @endif
</div>
