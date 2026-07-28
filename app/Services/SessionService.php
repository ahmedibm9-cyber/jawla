<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SessionService
{
    /**
     * @return array<int, object{id: string, user_id: int, user_name: string, user_email: string, ip_address: string|null, user_agent: string, last_activity: int, is_current: bool}>
     */
    public function listActiveSessions(int $currentSessionId): array
    {
        $sessions = DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.*', 'users.name as user_name', 'users.email as user_email')
            ->orderByDesc('sessions.last_activity')
            ->limit(100)
            ->get();

        return $sessions->map(fn ($s) => (object) [
            'id' => $s->id,
            'user_id' => $s->user_id,
            'user_name' => $s->user_name,
            'user_email' => $s->user_email,
            'ip_address' => $s->ip_address,
            'user_agent' => $this->parseUserAgent($s->user_agent),
            'last_activity' => $s->last_activity,
            'is_current' => $s->id === (string) $currentSessionId,
        ])->toArray();
    }

    public function revokeSession(string $sessionId): int
    {
        return (int) DB::table('sessions')
            ->where('id', $sessionId)
            ->delete();
    }

    public function revokeAllExceptCurrent(string $currentSessionId): int
    {
        return (int) DB::table('sessions')
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    public function isUserOnShift(int $userId): bool
    {
        return DB::table('work_sessions')
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->exists();
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
