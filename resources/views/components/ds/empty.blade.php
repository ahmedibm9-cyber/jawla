@props(['icon' => 'heroicon-o-inbox', 'message' => '', 'action' => null, 'actionLabel' => ''])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 text-center']) }}>
    @if($icon)
        <div class="mb-4 text-text-muted">
            <x-dynamic-component :component="$icon" class="mx-auto h-16 w-16" />
        </div>
    @endif
    @if($message)
        <p class="mb-4 text-lg text-text-secondary">{{ $message }}</p>
    @endif
    @if($action && $actionLabel)
        {{ $action }}
    @endif
</div>
