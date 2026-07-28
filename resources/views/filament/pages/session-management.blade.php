<x-filament-panels::page>
    @php
        $sessions = $this->getSessions();
    @endphp

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ count($sessions) }} active session(s)
            </p>
            <x-filament::button
                wire:click="revokeAllExceptCurrent"
                color="danger"
                size="sm"
            >
                Revoke All Others
            </x-filament::button>
        </div>

        <div class="space-y-3">
            @forelse ($sessions as $session)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-950 dark:text-white">
                                    {{ $session->user_name }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $session->user_email }}
                                </span>
                                @if ($session->is_current)
                                    <x-filament::badge color="success" size="sm">
                                        Current
                                    </x-filament::badge>
                                @endif
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                <span>{{ $session->ip_address ?? 'Unknown IP' }}</span>
                                <span>{{ $session->user_agent }}</span>
                                <span>{{ $session->last_active }}</span>
                            </div>
                        </div>

                        @unless ($session->is_current)
                            <x-filament::icon-button
                                wire:click="revokeSession('{{ $session->id }}')"
                                icon="heroicon-o-x-mark"
                                color="danger"
                                size="sm"
                                label="Revoke session"
                            />
                        @endunless
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                    No active sessions.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
