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
    public function recordPing(float $latitude, float $longitude, ?float $accuracy = null): void
    {
        $rep = Auth::user();
        if ($rep === null) {
            return;
        }

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
