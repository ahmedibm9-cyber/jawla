@php($isAr = app()->getLocale() === 'ar')
<div class="main-content">
    <x-page-header :title="$isAr ? 'قائمة المزامنة' : 'Sync queue'">
        <x-slot:icon><x-heroicon-o-cloud-arrow-up width="22" height="22" /></x-slot:icon>
    </x-page-header>

    <div class="page-body" x-data="jawlaSyncQueue">
        {{-- Summary + retry-all --}}
        <div class="card mb-4 flex items-center justify-between gap-3">
            <div>
                <div class="text-sm text-text-secondary">{{ $isAr ? 'بانتظار المزامنة' : 'Waiting to sync' }}</div>
                <div class="text-2xl font-bold" x-text="pending.length"></div>
            </div>
            <div class="text-center">
                <div class="text-sm text-text-secondary">{{ $isAr ? 'فشلت' : 'Failed' }}</div>
                <div class="text-2xl font-bold text-red-600" x-text="failed.length"></div>
            </div>
            <button type="button" class="btn btn-primary" x-show="pending.length > 0"
                x-on:click="retryAll()" :disabled="busy">
                {{ $isAr ? 'مزامنة الآن' : 'Sync now' }}
            </button>
        </div>

        {{-- Empty state --}}
        <template x-if="pending.length === 0 && failed.length === 0">
            <div class="card text-center text-text-secondary py-8">
                <x-heroicon-o-check-circle width="40" height="40" class="mx-auto mb-2 text-green-500" />
                <p>{{ $isAr ? 'كل شيء متزامن. لا توجد عناصر معلّقة.' : 'All synced. Nothing is queued.' }}</p>
            </div>
        </template>

        {{-- Item list --}}
        <template x-for="item in items" :key="item.id">
            <div class="card mb-2 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold" x-text="label(item.type)"></span>
                        <span class="text-xs px-2 py-0.5 rounded-full"
                            :class="item.status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'"
                            x-text="item.status === 'failed' ? '{{ $isAr ? 'فشل' : 'Failed' }}' : '{{ $isAr ? 'معلّق' : 'Pending' }}'"></span>
                    </div>
                    <div class="text-xs text-text-secondary mt-1" x-text="when(item.createdAt)"></div>
                    <div class="text-xs text-red-600 mt-1 break-words" x-show="item.error" x-text="item.error"></div>
                </div>
                <div class="flex flex-col gap-2 shrink-0">
                    <button type="button" class="btn btn-sm btn-outline"
                        x-on:click="retryItem(item.id)" :disabled="busy">
                        {{ $isAr ? 'إعادة' : 'Retry' }}
                    </button>
                    <button type="button" class="btn btn-sm text-red-600"
                        x-on:click="discardItem(item.id)" :disabled="busy">
                        {{ $isAr ? 'حذف' : 'Discard' }}
                    </button>
                </div>
            </div>
        </template>
    </div>

    <x-tab-bar active="more" />
</div>
