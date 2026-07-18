@props(['header' => null, 'footer' => null])
<div {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-surface shadow-sm']) }}>
    @if($header)
        <div class="border-b border-border px-6 py-4">
            {{ $header }}
        </div>
    @endif
    <div class="px-6 py-4">
        {{ $slot }}
    </div>
    @if($footer)
        <div class="border-t border-border px-6 py-3">
            {{ $footer }}
        </div>
    @endif
</div>
