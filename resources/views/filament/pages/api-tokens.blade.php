@php($ar = app()->getLocale() === 'ar')
<x-filament-panels::page>
    <div class="space-y-6">
        {{-- One-time plaintext token reveal --}}
        @if($plainTextToken)
            <x-filament::section>
                <x-slot name="heading">{{ $ar ? 'انسخ المفتاح الآن' : 'Copy your token now' }}</x-slot>
                <x-slot name="description">
                    {{ $ar
                        ? 'لن يظهر هذا المفتاح مرة أخرى. احفظه في مكان آمن — من يملكه يستطيع الوصول إلى بياناتك عبر الـ API.'
                        : 'This token will not be shown again. Store it securely — anyone with it can access your data via the API.' }}
                </x-slot>

                <div class="flex items-center gap-2" x-data="{ copied: false }">
                    <code class="flex-1 overflow-x-auto rounded-lg bg-gray-100 p-3 text-sm dark:bg-gray-800" dir="ltr">{{ $plainTextToken }}</code>
                    <x-filament::button
                        icon="heroicon-o-clipboard"
                        x-on:click="navigator.clipboard.writeText(@js($plainTextToken)); copied = true; setTimeout(() => copied = false, 2000)">
                        <span x-text="copied ? @js($ar ? 'تم النسخ' : 'Copied') : @js($ar ? 'نسخ' : 'Copy')"></span>
                    </x-filament::button>
                    <x-filament::button color="gray" wire:click="dismissPlainText">
                        {{ $ar ? 'تم' : 'Done' }}
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif

        {{-- Create token --}}
        <x-filament::section>
            <x-slot name="heading">{{ $ar ? 'إنشاء مفتاح جديد' : 'Create a new token' }}</x-slot>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="tokenName">{{ $ar ? 'اسم المفتاح' : 'Token name' }}</label>
                    <input id="tokenName" type="text" wire:model="tokenName"
                        placeholder="{{ $ar ? 'مثال: تكامل أودو' : 'e.g. Odoo integration' }}"
                        class="block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-600" />
                    @error('tokenName') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <span class="block text-sm font-medium mb-1">{{ $ar ? 'الصلاحيات' : 'Abilities' }}</span>
                    <div class="space-y-2">
                        @foreach($this->abilityOptions() as $key => $label)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="selectedAbilities" value="{{ $key }}"
                                    class="rounded border-gray-300 text-primary-600 dark:bg-gray-800 dark:border-gray-600" />
                                <span>{{ $label }}</span>
                                <code class="text-xs text-gray-400" dir="ltr">{{ $key }}</code>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedAbilities') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>

                <x-filament::button wire:click="createToken" icon="heroicon-o-plus" wire:loading.attr="disabled">
                    {{ $ar ? 'إنشاء المفتاح' : 'Create token' }}
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Existing tokens --}}
        <x-filament::section>
            <x-slot name="heading">{{ $ar ? 'المفاتيح الحالية' : 'Active tokens' }}</x-slot>

            <div class="overflow-x-auto"
                x-data="{ confirming: false, revokeId: null, revokeName: '' }">
                <table class="w-full text-sm" aria-label="{{ app()->getLocale() === 'ar' ? 'رموز API النشطة' : 'Active API tokens' }}">
                    <thead>
                        <tr class="text-start text-gray-500">
                            <th class="p-2 text-start">{{ $ar ? 'الاسم' : 'Name' }}</th>
                            <th class="p-2 text-start">{{ $ar ? 'الصلاحيات' : 'Abilities' }}</th>
                            <th class="p-2 text-start">{{ $ar ? 'آخر استخدام' : 'Last used' }}</th>
                            <th class="p-2 text-start">{{ $ar ? 'تاريخ الإنشاء' : 'Created' }}</th>
                            <th class="p-2 text-end">{{ $ar ? 'إجراء' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->tokens as $token)
                            <tr class="border-t">
                                <td class="p-2 font-medium">{{ $token->name }}</td>
                                <td class="p-2">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach((array) $token->abilities as $ability)
                                            <x-filament::badge color="gray" dir="ltr">{{ $ability }}</x-filament::badge>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-2" dir="ltr">{{ $token->last_used_at?->diffForHumans() ?? '—' }}</td>
                                <td class="p-2" dir="ltr">{{ $token->created_at?->format('Y-m-d') }}</td>
                                <td class="p-2 text-end">
                                    <x-filament::button size="sm" color="danger" icon="heroicon-o-trash"
                                        x-on:click="confirming = true; revokeId = {{ $token->id }}; revokeName = @js($token->name)">
                                        {{ $ar ? 'إلغاء' : 'Revoke' }}
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center text-gray-500">{{ $ar ? 'لا توجد مفاتيح' : 'No tokens yet' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Bilingual revoke confirmation modal --}}
                <div x-cloak x-show="confirming" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                    role="dialog" aria-modal="true" aria-label="{{ $ar ? 'إلغاء المفتاح' : 'Revoke token' }}"
                    x-on:keydown.escape.window="confirming = false">
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900" x-on:click.outside="confirming = false">
                        <h3 class="text-lg font-semibold">{{ $ar ? 'إلغاء المفتاح؟' : 'Revoke token?' }}</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            {{ $ar
                                ? 'سيتم إلغاء المفتاح «' : 'This will permanently revoke the token “' }}<span class="font-semibold" x-text="revokeName"></span>{{ $ar
                                ? '» نهائيًا، وسيتوقف أي تكامل يستخدمه عن العمل فورًا.'
                                : '” and immediately break any integration using it.' }}
                        </p>
                        <div class="mt-6 flex justify-end gap-2">
                            <x-filament::button color="gray" x-on:click="confirming = false">
                                {{ $ar ? 'تراجع' : 'Cancel' }}
                            </x-filament::button>
                            <x-filament::button color="danger"
                                x-on:click="$wire.revokeToken(revokeId); confirming = false">
                                {{ $ar ? 'إلغاء المفتاح' : 'Revoke token' }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
