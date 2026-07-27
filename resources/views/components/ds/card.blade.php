@props(['header' => null, 'footer' => null])
<div {{ $attributes->merge(['class' => 'rounded-xl border border-border-light bg-surface']) }}
     style="box-shadow:0 2px 6px rgba(0,0,0,0.04),0 1px 3px rgba(0,0,0,0.06)">
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
