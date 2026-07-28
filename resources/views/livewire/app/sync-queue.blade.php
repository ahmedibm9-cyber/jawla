<div class="main-content">
    <x-page-header :title="__('app.sync_queue')">
        <x-slot:icon><x-heroicon-o-cloud-arrow-up width="22" height="22" /></x-slot:icon>
    </x-page-header>

    <div class="page-body" x-data="jawlaSyncQueue" x-cloak>
        <div class="card text-center text-text-secondary py-8" x-show="loading">
            {{ __('app.sync_loading') }}
        </div>
        <div x-show="!loading">
        {{-- Summary + retry-all --}}
        <div class="card mb-4 flex items-center justify-between gap-3">
            <div>
                <div class="text-sm text-text-secondary">{{ __('app.sync_waiting') }}</div>
                <div class="text-2xl font-bold" x-text="pending.length"></div>
            </div>
            <div class="text-center">
                <div class="text-sm text-text-secondary">{{ __('app.sync_failed') }}</div>
                <div class="text-2xl font-bold text-red-600" x-text="failed.length"></div>
            </div>
            <div class="text-center">
                <div class="text-sm text-text-secondary">{{ __('app.sync_conflicts') }}</div>
                <div class="text-2xl font-bold text-amber-700" x-text="conflicts.length"></div>
            </div>
            <button type="button" class="btn btn-primary" x-show="pending.length > 0"
                x-on:click="retryAll()" :disabled="busy">
                {{ __('app.sync_now') }}
            </button>
        </div>

        {{-- Empty state --}}
        <template x-if="pending.length === 0 && failed.length === 0 && conflicts.length === 0">
            <div class="card text-center text-text-secondary py-8">
                <x-heroicon-o-check-circle width="40" height="40" class="mx-auto mb-2 text-green-500" />
                <p>{{ __('app.sync_all_synced') }}</p>
            </div>
        </template>

        {{-- Item list --}}
        <template x-for="item in items" :key="item.id">
            <div class="card mb-2 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold" x-text="label(item.type)"></span>
                        <span class="text-xs px-2 py-0.5 rounded-full"
                            :class="item.status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : (item.status === 'conflict' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400')"
                            x-text="item.status === 'failed' ? '{{ __('app.sync_failed') }}' : (item.status === 'conflict' ? '{{ __('app.sync_conflict') }}' : '{{ __('app.sync_pending') }}')"></span>
                    </div>
                    <div class="text-xs text-text-secondary mt-1" x-text="when(item.createdAt)"></div>
                    <div class="text-xs text-red-600 mt-1 break-words" x-show="item.error" x-text="item.error"></div>
                </div>
                <div class="flex flex-col gap-2 shrink-0">
                    <button type="button" class="btn btn-sm btn-outline" x-show="item.status !== 'conflict'"
                        x-on:click="retryItem(item.id)" :disabled="busy">
                        {{ __('app.sync_retry') }}
                    </button>
                    <button type="button" class="btn btn-sm text-red-600"
                        x-on:click="if(confirm('{{ __('app.sync_discard_confirm') }}')) discardItem(item.id)" :disabled="busy">
                        {{ __('app.sync_discard') }}
                    </button>
                </div>
            </div>
        </template>
        </div>
    </div>

    <x-tab-bar active="more" />
</div>
