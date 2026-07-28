<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SessionManagement extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';
    protected static string|null $navigationLabel = 'Sessions';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?string $title = 'Active Sessions';
    protected static ?int $navigationSort = 50;
    protected static ?string $slug = 'admin/sessions';
    protected string $view = 'filament.pages.session-management';

    /** @return array<int, object{id: string, user_name: string, user_email: string, ip_address: string|null, user_agent: string, last_active: string, is_current: bool}> */
    public function getSessions(): array
    {
        $sessions = DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.*', 'users.name as user_name', 'users.email as user_email')
            ->orderByDesc('sessions.last_activity')
            ->get();

        return $sessions->map(fn ($s) => (object) [
            'id' => $s->id,
            'user_name' => $s->user_name,
            'user_email' => $s->user_email,
            'ip_address' => $s->ip_address,
            'user_agent' => $this->parseUserAgent($s->user_agent),
            'last_active' => now()->diffInSeconds(now()->subSeconds(now()->timestamp - $s->last_activity)) . 's ago',
            'is_current' => $s->id === session()->getId(),
        ])->toArray();
    }

    public function revokeSession(string $sessionId): void
    {
        DB::table('sessions')->where('id', $sessionId)->delete();

        Notification::make()
            ->title('Session revoked')
            ->success()
            ->send();
    }

    public function revokeAllExceptCurrent(): void
    {
        $currentId = session()->getId();

        $revoked = DB::table('sessions')
            ->where('id', '!=', $currentId)
            ->delete();

        Notification::make()
            ->title("Revoked {$revoked} other sessions")
            ->success()
            ->send();
    }

    private function parseUserAgent(?string $ua): string
    {
        if (! $ua) {
            return 'Unknown';
        }

        if (str_contains($ua, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($ua, 'Edg')) {
            return 'Edge';
        }
        if (str_contains($ua, 'Chrome')) {
            return 'Chrome';
        }
        if (str_contains($ua, 'Safari')) {
            return 'Safari';
        }

        return substr($ua, 0, 40) . '...';
    }
}
