@props(['variant' => 'text', 'width' => '100%', 'height' => null])
@php
    $classes = 'animate-pulse rounded bg-surface-alt';
    $styles = match($variant) {
        'text' => 'height: 1em; width: ' . $width,
        'avatar' => 'width: 40px; height: 40px; border-radius: 50%;',
        'button' => 'height: 38px; width: ' . $width,
        'card' => 'height: 200px; width: 100%;',
        default => 'height: 1em; width: ' . $width,
    };
    if ($height) {
        $styles = 'height: ' . $height . '; width: ' . $width;
    }
@endphp
<div class="{{ $classes }}" style="{{ $styles }}"></div>
