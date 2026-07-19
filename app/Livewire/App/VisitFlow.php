<?php

namespace App\Livewire\App;

use App\Models\Activity;
use App\Models\Visit;
use App\Models\VisitReport;
use App\Support\GpsCoordinate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class VisitFlow extends Component
{
    public Visit $visit;

    public string $step = 'checkin';

    public string $summary = '';

    public string $customerFeedback = '';

    public string $actionTaken = '';

    public bool $followUpNeeded = false;

    public string $followUpNote = '';

    public string $signature = '';

    public ?float $userLat = null;

    public ?float $userLng = null;

    public ?float $userAccuracy = null;

    public ?float $distanceMeters = null;

    public bool $withinRange = false;

    public bool $gpsDenied = false;

    public string $errorMessage = '';

    public function mount(Visit $visit): void
    {
        abort_unless($visit->user_id === auth()->id(), 403);

        $this->visit = $visit->load('customer.company');

        if ($visit->arrival_confirmed) {
            $this->step = 'report';
        }
    }

    public function checkGps(): void
    {
        $this->gpsDenied = false;

        // GPS coordinates come from JavaScript callback
        if ($this->userLat === null || $this->userLng === null) {
            $this->errorMessage = app()->getLocale() === 'ar'
                ? 'يرجى السماح بالوصول إلى الموقع'
                : 'Please allow location access';

            return;
        }

        $distance = $this->distanceToCustomer();
        $this->distanceMeters = round($distance);
        $this->withinRange = $distance <= $this->geofenceRadius();

        if ($this->withinRange) {
            $this->confirmArrival();
        } else {
            $this->logDeclinedAttempt($distance);
        }
    }

    public function markGpsDenied(): void
    {
        $this->gpsDenied = true;
        $this->userLat = null;
        $this->userLng = null;
        $this->withinRange = false;
    }

    public function confirmArrival(): void
    {
        // Idempotent: an already-confirmed visit just resumes at the report step.
        if ($this->visit->arrival_confirmed) {
            $this->step = 'report';

            return;
        }

        // D-02: GPS is mandatory — no coordinates, no check-in.
        if ($this->userLat === null || $this->userLng === null) {
            $this->gpsDenied = true;

            return;
        }

        // D-02: server-side recompute — never trust client-set withinRange.
        $distance = $this->distanceToCustomer();
        $radius = $this->geofenceRadius();

        if ($distance > $radius) {
            $this->withinRange = false;
            $this->distanceMeters = round($distance);
            $this->logDeclinedAttempt($distance);
            $this->errorMessage = __('app.out_of_range_blocked', [
                'distance' => round($distance),
                'radius' => $radius,
            ]);

            return;
        }

        $this->visit->update([
            'checkin_latitude' => $this->userLat,
            'checkin_longitude' => $this->userLng,
            'checkin_at' => now(),
            'arrival_confirmed' => true,
            'arrival_confirmed_at' => now(),
            'arrival_flag' => 'in_range',
            'checkin_distance_m' => (int) round($distance),
            'checkin_accuracy_m' => $this->userAccuracy !== null ? (int) round($this->userAccuracy) : null,
            'status' => 'open',
        ]);

        $this->withinRange = true;
        $this->step = 'report';
    }

    public function submitReport(): void
    {
        $this->validate([
            'summary' => 'required|string|min:5',
        ]);

        DB::transaction(function () {
            $signaturePath = null;

            if ($this->signature) {
                $path = 'signatures/'.$this->visit->id.'_'.time().'.png';
                $data = explode(',', $this->signature, 2);
                $imgData = base64_decode($data[1] ?? '');
                Storage::disk('private')->put($path, $imgData);
                $signaturePath = $path;
            }

            VisitReport::create([
                'visit_id' => $this->visit->id,
                'summary' => $this->summary,
                'customer_feedback' => $this->customerFeedback ?: null,
                'action_taken' => $this->actionTaken ?: null,
                'follow_up_needed' => $this->followUpNeeded,
                'follow_up_note' => $this->followUpNote ?: null,
                'submitted_at' => now(),
                'signature_path' => $signaturePath,
            ]);

            $this->visit->update(['status' => 'closed']);
        });

        $this->step = 'done';
    }

    private function distanceToCustomer(): float
    {
        $userPos = new GpsCoordinate((float) $this->userLat, (float) $this->userLng);
        $customerPos = new GpsCoordinate(
            (float) ($this->visit->customer->latitude ?? 0),
            (float) ($this->visit->customer->longitude ?? 0),
        );

        return $userPos->metersTo($customerPos);
    }

    private function geofenceRadius(): int
    {
        return (int) ($this->visit->customer->company?->geofence_radius_m ?? 500);
    }

    private function logDeclinedAttempt(float $distance): void
    {
        Activity::log('geofence_declined', $this->visit, sprintf(
            'Check-in declined: %dm from customer (radius %dm)',
            (int) round($distance),
            $this->geofenceRadius(),
        ), [
            'latitude' => $this->userLat,
            'longitude' => $this->userLng,
            'accuracy_m' => $this->userAccuracy,
            'distance_m' => (int) round($distance),
            'radius_m' => $this->geofenceRadius(),
        ]);
    }

    public function render()
    {
        return view('livewire.app.visit-flow', [
            'customer' => $this->visit->customer,
        ]);
    }
}
