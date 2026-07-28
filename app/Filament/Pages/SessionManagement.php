<?php

namespace App\Filament\Pages;

use App\Services\SessionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SessionManagement extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Sessions';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $title = 'Active Sessions';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'admin/sessions';

    protected string $view = 'filament.pages.session-management';

    public function getSessions(): array
    {
        return app(SessionService::class)
            ->listActiveSessions((int) session()->getId());
    }

    public function revokeSession(string $sessionId): void
    {
        app(SessionService::class)->revokeSession($sessionId);

        Notification::make()
            ->title(__('app.session_revoked'))
            ->success()
            ->send();
    }

    public function revokeAllExceptCurrent(): void
    {
        $revoked = app(SessionService::class)
            ->revokeAllExceptCurrent(session()->getId());

        Notification::make()
            ->title(__('app.sessions_revoked_count', ['count' => $revoked]))
            ->success()
            ->send();
    }
}
