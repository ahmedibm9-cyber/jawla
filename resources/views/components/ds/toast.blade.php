@props(['type' => 'success', 'message' => ''])

<div class="toast toast-{{ $type }} relative top-0 mb-4" style="transform:none"
     @if($type === 'success') aria-live="polite" @else role="alert" @endif>
    {{ $message }}
</div>
