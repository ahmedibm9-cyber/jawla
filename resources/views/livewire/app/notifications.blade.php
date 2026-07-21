<div>
<x-pull-to-refresh>
<div class="main-content">
    <x-page-header :title="__('app.notifications')" />

    <div class="page-body">
        <div wire:loading.delay class="space-y-2 mb-3" aria-hidden="true">
            <x-ds.skeleton height="72px" />
            <x-ds.skeleton height="72px" />
        </div>

        @if($notifications->isEmpty())
            <x-ds.empty icon="heroicon-o-bell" :message="__('app.no_notifications')">
                <x-slot:action>
                    <a href="/app" class="btn btn-primary no-underline">{{ __('app.back_home') }}</a>
                </x-slot:action>
            </x-ds.empty>
        @else
            @php $isAr = app()->getLocale() === 'ar'; @endphp
            @foreach($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isNew = in_array($notification->id, $newIds, true);
                    $isCritical = ($data['severity'] ?? 'info') === 'critical';
                    $notifUrl = $data['url'] ?? '/app';
                    // Sanitize URL — only allow relative paths or http(s) URLs
                    $notifUrl = (str_starts_with($notifUrl, '/') || str_starts_with($notifUrl, 'http'))
                        ? $notifUrl
                        : '/app';
                @endphp
                <a href="{{ $notifUrl }}"
                   class="card block no-underline text-inherit mb-2 {{ $isNew ? 'border-2 border-accent' : '' }}">
                    <div class="flex items-start gap-2">
                        @if($isCritical)
                            <span class="mt-1 shrink-0 inline-block w-2.5 h-2.5 rounded-full bg-red-600" aria-hidden="true"></span>
                        @endif
                        <div class="min-w-0">
                            <strong class="block">{{ $isAr ? ($data['title_ar'] ?? '') : ($data['title_en'] ?? '') }}</strong>
                            <p class="m-0 text-sm text-text-secondary">{{ $isAr ? ($data['body_ar'] ?? '') : ($data['body_en'] ?? '') }}</p>
                            <small class="text-text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                </a>
            @endforeach

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>

<x-tab-bar active="" />
</x-pull-to-refresh>
</div>
