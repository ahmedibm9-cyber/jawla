@props(['header' => null, 'footer' => null])
<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if($header)
        <div class="border-b border-gray-200 px-6 py-4">
            {{ $header }}
        </div>
    @endif
    <div class="px-6 py-4">
        {{ $slot }}
    </div>
    @if($footer)
        <div class="border-t border-gray-200 px-6 py-3">
            {{ $footer }}
        </div>
    @endif
</div>
