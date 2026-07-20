<?php

namespace App\Services;

use App\Models\LocationPing;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Support\Carbon;

/**
 * Records rep location pings for the manager live map (CG3). Tracking is
 * on-shift-only: a ping is stored only while the rep has an open work session
 * (ended_at IS NULL). A short dedupe window drops bursts so a chatty client
 * cannot flood the table. This is deliberately not a money/stock path — a plain
 * insert, no transaction.
 */
class LocationPingService
{
    /** Minimum seconds between stored pings for a rep. */
    private const DEDUPE_SECONDS = 15;

    public function record(User $rep, float $latitude, float $longitude, ?float $accuracy = null): ?LocationPing
    {
        $session = WorkSession::query()
            ->where('user_id', $rep->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        // Off-shift: do not track.
        if ($session === null) {
            return null;
        }

        $recent = LocationPing::query()
            ->where('user_id', $rep->id)
            ->where('recorded_at', '>=', Carbon::now()->subSeconds(self::DEDUPE_SECONDS))
            ->exists();

        if ($recent) {
            return null;
        }

        return LocationPing::create([
            'company_id' => $rep->company_id,
            'user_id' => $rep->id,
            'work_session_id' => $session->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'recorded_at' => Carbon::now(),
        ]);
    }

    /**
     * Latest ping per rep seen within the last $minutes, for the current company
     * scope. Shaped for the manager map.
     *
     * @return array<int, array{name: string, lat: float, lng: float, accuracy: ?float, seen_at: string, minutes_ago: int}>
     */
    public function latestPerRep(int $minutes = 30): array
    {
        $cutoff = Carbon::now()->subMinutes($minutes);

        return LocationPing::query()
            ->with('user')
            ->where('recorded_at', '>=', $cutoff)
            ->orderByDesc('recorded_at')
            ->get()
            ->unique('user_id')
            ->map(fn (LocationPing $p) => [
                'name' => $p->user?->name ?? '—',
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'accuracy' => $p->accuracy !== null ? (float) $p->accuracy : null,
                'seen_at' => $p->recorded_at->format('H:i'),
                'minutes_ago' => (int) $p->recorded_at->diffInMinutes(Carbon::now()),
            ])
            ->values()
            ->all();
    }
}
