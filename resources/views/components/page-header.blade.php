@props(['title', 'subtitle' => null, 'icon' => null])

<div class="page-header">
    <div class="page-header-content">
        @if($icon)
            <div class="page-header-icon">{!! $icon !!}</div>
        @endif
        <div>
            <h2 class="page-header-title">{{ $title }}</h2>
            @if($subtitle)
                <p class="page-header-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
</div>
