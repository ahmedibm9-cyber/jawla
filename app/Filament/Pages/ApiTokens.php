<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\ApiTokenService;
use App\Support\ApiAbilities;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Admin management for public-API tokens (CG4). Mints scoped Sanctum tokens for
 * integrations and revokes them. The plaintext token is shown exactly once,
 * right after creation — Sanctum never stores it in a recoverable form. All
 * mutations run through ApiTokenService (validated + activity-logged); revoke is
 * gated behind a bilingual confirmation modal.
 */
class ApiTokens extends Page
{
    protected string $view = 'filament.pages.api-tokens';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    public string $tokenName = '';

    /** @var list<string> */
    public array $selectedAbilities = [];

    public ?string $plainTextToken = null;

    public static function getNavigationLabel(): string
    {
        return app()->getLocale() === 'ar' ? 'مفاتيح الـ API' : 'API Tokens';
    }

    public function getTitle(): string
    {
        return self::getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar' ? 'الإعدادات' : 'Settings';
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    /** Bilingual ability labels for the create form. */
    public function abilityOptions(): array
    {
        return ApiAbilities::options();
    }

    /**
     * Tokens belonging to users in the current admin's company.
     *
     * @return Collection<int, PersonalAccessToken>
     */
    public function getTokensProperty(): Collection
    {
        $companyUserIds = User::forCompany(Auth::user()->activeCompanyId())->pluck('id');

        return PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->whereIn('tokenable_id', $companyUserIds)
            ->latest()
            ->get();
    }

    public function createToken(): void
    {
        abort_unless(self::canAccess(), 403);

        $this->validate([
            'tokenName' => 'required|string|max:100',
            'selectedAbilities' => 'required|array|min:1',
            'selectedAbilities.*' => 'in:'.implode(',', ApiAbilities::keys()),
        ]);

        try {
            $result = app(ApiTokenService::class)->issue(Auth::user(), $this->tokenName, $this->selectedAbilities);
            $this->plainTextToken = $result['plainTextToken'];
            $this->reset(['tokenName', 'selectedAbilities']);

            Notification::make()
                ->success()
                ->title(app()->getLocale() === 'ar' ? 'تم إنشاء المفتاح' : 'Token created')
                ->send();
        } catch (\InvalidArgumentException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }

    public function revokeToken(int $tokenId): void
    {
        abort_unless(self::canAccess(), 403);

        $companyUserIds = User::forCompany(Auth::user()->activeCompanyId())->pluck('id');

        $token = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->whereIn('tokenable_id', $companyUserIds)
            ->find($tokenId);

        if ($token === null) {
            return;
        }

        app(ApiTokenService::class)->revoke($token);

        Notification::make()
            ->success()
            ->title(app()->getLocale() === 'ar' ? 'تم إلغاء المفتاح' : 'Token revoked')
            ->send();
    }

    public function dismissPlainText(): void
    {
        $this->plainTextToken = null;
    }
}
