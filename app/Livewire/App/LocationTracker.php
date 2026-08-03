<?php

namespace App\Livewire\App;

use App\Services\LocationPingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

/**
 * Invisible rep-side location beacon (CG3). Mounted once in the rep PWA layout;
 * a gentle JS loop posts the browser geolocation via the recordPing action.
 * The action validates coordinates server-side and delegates to
 * LocationPingService, which stores a ping only while the rep is on-shift and
 * dedupes bursts. Off-shift pings are silently dropped — no client trust.
 */
class LocationTracker extends Component
{
    public bool $showNotice = false;
    // ponytail: notice bar removed — fixed z-index:9998 intercepted pointer
    // events across the entire bottom 4.5rem of every rep page. Beacon still
    // active. Re-add as non-blocking toast if legal requires disclosure.

    public function mount(): void {}

    public function recordPing(float $latitude, float $longitude, ?float $accuracy = null): void
    {
        $rep = Auth::user();
        if ($rep === null) {
            return;
        }

        // Rate limit: max 1 ping per 30 seconds per user
        $lastPingKey = 'location_ping_last_'.$rep->id;
        if (cache()->has($lastPingKey)) {
            return;
        }
        cache()->put($lastPingKey, true, 30);

        $validator = Validator::make(
            compact('latitude', 'longitude', 'accuracy'),
            [
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'accuracy' => ['nullable', 'numeric', 'min:0'],
            ]
        );

        if ($validator->fails()) {
            return;
        }

        app(LocationPingService::class)->record($rep, $latitude, $longitude, $accuracy);
    }

    public function render()
    {
        return view('livewire.app.location-tracker');
    }
}
