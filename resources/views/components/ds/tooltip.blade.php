@props(['content' => '', 'position' => 'top'])
<span class="group relative inline-block">
    <span class="cursor-pointer">{{ $slot }}</span>
    <span class="pointer-events-none absolute z-50 scale-0 rounded-md bg-gray-900 px-2 py-1 text-xs text-white transition-all group-hover:scale-100
        {{ $position === 'top' ? 'bottom-full left-1/2 -translate-x-1/2 mb-1' : '' }}
        {{ $position === 'bottom' ? 'top-full left-1/2 -translate-x-1/2 mt-1' : '' }}
    ">
        {{ $content }}
    </span>
</span>
