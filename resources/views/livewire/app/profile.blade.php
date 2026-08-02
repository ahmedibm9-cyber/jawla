<div class="main-content">
    <x-page-header :title="__('app.profile')">
        <x-slot:icon><svg aria-hidden="true" fill='none' stroke='currentColor' viewBox='0 0 24 24' width='22' height='22'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'/></svg></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($success)
            <x-ds.toast type="success" :message="$successMessage" />
        @endif

        <div wire:loading.delay class="space-y-4" aria-hidden="true">
            <x-ds.skeleton height="72px" />
            <x-ds.skeleton height="72px" />
            <x-ds.skeleton height="72px" />
        </div>

        @if($editing)
            {{-- Edit Mode --}}
            <form wire:submit="save">
                <div class="card">
                    <h3 class="m-0 mb-4 text-base font-semibold">{{ __('app.personal_info') }}</h3>

                    <div class="form-group">
                        <label for="name" class="form-label">{{ __('app.full_name') }} *</label>
                        <input wire:model="name" id="name" type="text" autocomplete="name" class="form-input" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                        @error('name') <small id="name-error" class="form-error">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">{{ __('app.email') }} *</label>
                        <input wire:model="email" id="email" type="email" inputmode="email" autocomplete="email" spellcheck="false" class="form-input" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        @error('email') <small id="email-error" class="form-error">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">{{ __('app.phone') }}</label>
                        <input wire:model="phone" id="phone" type="tel" inputmode="tel" autocomplete="tel" class="form-input" @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                        @error('phone') <small id="phone-error" class="form-error">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="card">
                    <h3 class="m-0 mb-4 text-base font-semibold">{{ __('app.change_password') }}</h3>
                    <p class="text-text-secondary text-sm mb-4">{{ __('app.password_min_chars') }}</p>

                    <div class="form-group">
                        <label for="currentPassword" class="form-label">{{ __('app.current_password') }}</label>
                        <input wire:model="currentPassword" id="currentPassword" type="password" autocomplete="current-password" class="form-input" @error('currentPassword') aria-invalid="true" aria-describedby="currentPassword-error" @enderror>
                        @error('currentPassword') <small id="currentPassword-error" class="form-error">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label for="newPassword" class="form-label">{{ __('app.new_password') }}</label>
                        <input wire:model="newPassword" id="newPassword" type="password" autocomplete="new-password" class="form-input" @error('newPassword') aria-invalid="true" aria-describedby="newPassword-error" @enderror>
                        @error('newPassword') <small id="newPassword-error" class="form-error">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label for="newPasswordConfirmation" class="form-label">{{ __('app.confirm_password') }}</label>
                        <input wire:model="newPasswordConfirmation" id="newPasswordConfirmation" type="password" autocomplete="new-password" class="form-input">
                    </div>
                </div>

                <div class="flex gap-3 mb-4">
                    <button type="button" class="btn btn-outline flex-1" wire:click="toggleEdit">
                        {{ __('app.cancel') }}
                    </button>
                    <div class="flex-1">
                        <x-ds.modal :title="__('app.save_changes')" :message="__('app.profile_info')">
                            <x-slot:trigger>
                                <button type="button" class="btn btn-primary w-full">
                                    <span wire:loading.remove>{{ __('app.save_changes') }}</span>
                                    <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                                </button>
                            </x-slot:trigger>
                            <x-slot:confirm>
                                <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">{{ __('app.confirm') }}</button>
                            </x-slot:confirm>
                        </x-ds.modal>
                    </div>
                </div>
            </form>
        @else
            {{-- View Mode --}}
            <div class="card">
                <div class="profile-field">
                    <span class="profile-field-label">{{ __('app.full_name') }}</span>
                    <span class="profile-field-value">{{ $user->name }}</span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">{{ __('app.email') }}</span>
                    <span class="profile-field-value">{{ $user->email }}</span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">{{ __('app.phone') }}</span>
                    <span class="profile-field-value">{{ $user->phone ?? '—' }}</span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">{{ __('app.employee_code') }}</span>
                    <span class="profile-field-value">{{ $user->employee_code ?? '—' }}</span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">{{ __('app.company') }}</span>
                    <span class="profile-field-value">{{ app()->getLocale() === 'ar' ? ($user->company?->name_ar ?? '—') : ($user->company?->name_en ?? $user->company?->name_ar ?? '—') }}</span>
                </div>
            </div>

            <button class="btn btn-primary w-full mb-4" wire:click="toggleEdit">
                {{ __('app.edit_profile') }}
            </button>
        @endif
    </div>

    <x-tab-bar active="more" />
</div>
