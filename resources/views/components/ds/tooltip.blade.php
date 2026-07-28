@props(['content' => '', 'position' => 'top'])
@php $id = 'tooltip-' . md5($content); @endphp
<span class="group relative inline-block">
    <span class="cursor-pointer" aria-describedby="{{ $id }}">{{ $slot }}</span>
    <span id="{{ $id }}" role="tooltip" class="pointer-events-none absolute z-50 scale-0 rounded-md bg-surface-alt text-text-primary border border-border px-2 py-1 text-xs transition-all group-hover:scale-100
        {{ $position === 'top' ? 'bottom-full left-1/2 -translate-x-1/2 mb-1' : '' }}
        {{ $position === 'bottom' ? 'top-full left-1/2 -translate-x-1/2 mt-1' : '' }}
    ">
        {{ $content }}
    </span>
</span>
