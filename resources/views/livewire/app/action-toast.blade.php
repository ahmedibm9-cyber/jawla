@php($isAr = app()->getLocale() === 'ar')
<div aria-live="polite">
    @if($undone)
        <div class="action-toast" role="status"
            x-data x-init="setTimeout(() => $wire.dismiss(), 3500)">
            <span class="flex items-center gap-2">
                <x-heroicon-o-arrow-uturn-left width="18" height="18" />
                {{ $isAr ? 'تم التراجع عن العملية' : 'Action undone' }}
            </span>
        </div>
    @elseif($type)
        <div class="action-toast" role="status"
            x-data x-init="setTimeout(() => $wire.dismiss(), 12000)">
            <span class="min-w-0 truncate">{{ $error ?: $message }}</span>
            <span class="flex items-center gap-1 shrink-0">
                @unless($error)
                    <button type="button" wire:click="undo" wire:loading.attr="disabled"
                        class="action-toast-undo">
                        {{ $isAr ? 'تراجع' : 'Undo' }}
                    </button>
                @endunless
                <button type="button" wire:click="dismiss"
                    class="action-toast-close" aria-label="{{ $isAr ? 'إغلاق' : 'Dismiss' }}">&times;</button>
            </span>
        </div>
    @endif
</div>
