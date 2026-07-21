@php($arPhoto = app()->getLocale() === 'ar')
<div class="form-group">
    <label for="photo-capture-input" class="form-label">{{ $arPhoto ? 'الصور' : 'Photos' }}</label>

    {{-- accept=image + capture=environment opens the rear camera on mobile,
         with the gallery as a fallback on unsupported browsers. --}}
    <input
        id="photo-capture-input"
        type="file"
        accept="image/*"
        capture="environment"
        wire:model="photo"
        class="form-input"
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
