<div class="main-content">
    <x-page-header :title="__('app.settings')">
        <x-slot:icon><svg aria-hidden="true" fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'/><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        {{-- Language --}}
        <div class="card mb-4">
            <h3 class="m-0 mb-3 text-base font-semibold">{{ __('app.language') }}</h3>
            <div class="settings-lang-btns">
                <a href="/locale/ar" class="settings-lang-btn {{ $currentLocale === 'ar' ? 'active' : '' }}" @if($currentLocale === 'ar') aria-current="true" @endif>
                    {{ __('app.arabic') }}
                </a>
                <a href="/locale/en" class="settings-lang-btn {{ $currentLocale === 'en' ? 'active' : '' }}" @if($currentLocale === 'en') aria-current="true" @endif>
                    {{ __('app.english') }}
                </a>
            </div>
        </div>

        {{-- Account Info --}}
        <div class="card mb-4">
            <h3 class="m-0 mb-3 text-base font-semibold">{{ __('app.account') }}</h3>
            <div class="profile-field">
                <span class="profile-field-label">{{ __('app.employee_code') }}</span>
                <span class="profile-field-value">{{ $user->employee_code ?? '—' }}</span>
            </div>
            <div class="profile-field">
                <span class="profile-field-label">{{ __('app.company') }}</span>
                <span class="profile-field-value">{{ $user->company?->name ?? '—' }}</span>
            </div>
            <div class="profile-field">
                <span class="profile-field-label">{{ __('app.app_version') }}</span>
                <span class="profile-field-value">1.0.0</span>
            </div>
        </div>

        {{-- Logout --}}
        <div class="mb-4">
            <form action="/app/logout" method="POST" aria-label="{{ __('app.logout') }}">
                @csrf
                <button type="submit" class="more-logout-btn">
                    <svg aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    {{ __('app.logout') }}
                </button>
            </form>
        </div>
    </div>

    <x-tab-bar active="more" />
</div>
