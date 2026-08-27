<div class="main-content">
    <x-page-header :title="__('app.log_call')">
        <x-slot:icon><x-heroicon-o-phone class="w-6 h-6" /></x-slot:icon>
    </x-page-header>

    <div class="page-body">
        @if($successMessage)
            <x-ds.toast type="success" :message="$successMessage" />
        @endif
        @if($errorMessage)
            <x-ds.toast type="error" :message="$errorMessage" />
        @endif

        @if($customer)
            <div class="card mb-4">
                <h2 class="font-semibold text-base">{{ l($customer->name_ar, $customer->name_en ?? $customer->name_ar) }}</h2>
            </div>
        @endif

        <form wire:submit="saveCall">
            <div class="space-y-4">
                <label class="block text-sm font-medium">
                    {{ __('app.call_direction') }}
                    <select wire:model="direction" class="form-input mt-1">
                        <option value="outbound">{{ __('app.call_outbound') }}</option>
                        <option value="inbound">{{ __('app.call_inbound') }}</option>
                    </select>
                </label>

                @if($contacts->count() > 0)
                    <label class="block text-sm font-medium">
                        {{ __('app.call_contact') }}
                        <select wire:model="contactId" class="form-input mt-1">
                            <option value="">{{ __('app.call_no_contact') }}</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->name }} - {{ $contact->phone }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="block text-sm font-medium">
                    {{ __('app.call_outcome') }}
                    <select wire:model="outcome" class="form-input mt-1">
                        <option value="reached">{{ __('app.call_outcome_reached') }}</option>
                        <option value="no_answer">{{ __('app.call_outcome_no_answer') }}</option>
                        <option value="busy">{{ __('app.call_outcome_busy') }}</option>
                        <option value="left_voicemail">{{ __('app.call_outcome_left_voicemail') }}</option>
                    </select>
                </label>

                <label class="block text-sm font-medium">
                    {{ __('app.call_notes') }}
                    <textarea wire:model="notes" class="form-input mt-1" rows="3"></textarea>
                </label>

                <div class="card">
                    <div class="text-center">
                        <p class="text-2xl font-mono" id="timer">{{ gmdate('H:i:s', $durationSeconds) }}</p>
                        <div class="mt-4 flex gap-2">
                            @if(!$isRunning)
                                <button type="button" wire:click="startTimer" class="btn btn-primary flex-1">
                                    {{ __('app.call_start') }}
                                </button>
                            @else
                                <button type="button" wire:click="stopTimer" class="btn btn-secondary flex-1">
                                    {{ __('app.call_stop') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" @disabled($durationSeconds < 1)>
                    <span wire:loading.remove>{{ __('app.call_save') }}</span>
                    <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                </button>
            </div>
        </form>
    </div>

    <x-tab-bar active="more" />
</div>
