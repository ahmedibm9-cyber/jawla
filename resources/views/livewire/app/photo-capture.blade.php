@php($arPhoto = app()->getLocale() === 'ar')
<div class="form-group">
    <label for="photo-capture-input" class="form-label">{{ $arPhoto ? 'الصور' : 'Photos' }}</label>

    {{-- accept=image + capture=environment opens the rear camera on mobile,
         with the gallery as a fallback on unsupported browsers. The native
         input is visually hidden and triggered by a styled, localized label so
         the control matches the design system and reads correctly in RTL. --}}
    <label for="photo-capture-input" class="photo-capture-btn">
        <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>{{ $arPhoto ? 'التقاط صورة' : 'Add photo' }}</span>
    </label>
    <input
        id="photo-capture-input"
        type="file"
        accept="image/*"
        capture="environment"
        wire:model="photo"
        class="photo-capture-input-hidden"
    >
    <div wire:loading wire:target="photo" class="text-text-secondary text-sm mt-1">
        {{ $arPhoto ? 'جارٍ الرفع…' : 'Uploading…' }}
    </div>
    @error('photo')
        <p class="text-danger text-sm mt-1">{{ $message }}</p>
    @enderror

    @if(!empty($stored))
        <div class="grid grid-cols-3 gap-2 mt-2">
            @foreach($stored as $p)
                <div class="relative">
                    <img src="{{ $p['url'] }}" alt="{{ $p['name'] }}"
                        class="w-full h-24 object-cover rounded-lg border border-border">
                    <button type="button" wire:click="removePhoto({{ $p['id'] }})"
                        aria-label="{{ $arPhoto ? 'حذف الصورة' : 'Remove photo' }}"
                        class="absolute top-1 end-1 bg-danger text-white rounded-full w-11 h-11 flex items-center justify-center text-sm border-0 cursor-pointer">
                        &times;
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
