<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class EtaSettings extends Page
{
    protected static string $view = 'filament.pages.eta-settings';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-check';

    public static function getNavigationLabel(): string
    {
        return l('إعدادات الفوترة الإلكترونية', 'ETA E-Invoicing');
    }

    public function getTitle(): string
    {
        return self::getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return l('الإعدادات', 'Settings');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['admin', 'super_admin']) ?? false;
    }

    public function getConfigStatus(): array
    {
        return [
            'enabled' => config('eta.enabled', false),
            'environment' => config('eta.environment', 'preprod'),
            'has_client_id' => (string) config('eta.client_id') !== '',
            'has_client_secret' => (string) config('eta.client_secret') !== '',
            'has_taxpayer_rin' => (string) config('eta.taxpayer_rin') !== '',
            'has_api_url' => (string) config('eta.api_base_url') !== '',
            'has_id_url' => (string) config('eta.id_base_url') !== '',
        ];
    }

    public function getRecentSubmissions(): Collection
    {
        return Invoice::whereNotNull('eta_status')
            ->with('company:id,name_en,name_ar')
            ->orderByDesc('eta_submitted_at')
            ->limit(20)
            ->get();
    }
}
