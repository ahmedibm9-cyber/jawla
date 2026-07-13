<?php

namespace App\Livewire\App;

use App\Models\Visit;
use App\Models\VisitReport;
use App\Support\GpsCoordinate;
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

    public ?float $distanceMeters = null;

    public bool $withinRange = false;

    public bool $outOfRangeConfirmed = false;

    public string $errorMessage = '';

    public function mount(Visit $visit): void
    {
        $this->visit = $visit;

        if ($visit->arrival_confirmed) {
            $this->step = 'report';
        }
    }

    public function checkGps(): void
    {
        // GPS coordinates come from JavaScript callback
        if ($this->userLat === null || $this->userLng === null) {
            $this->errorMessage = app()->getLocale() === 'ar'
                ? 'يرجى السماح بالوصول إلى الموقع'
                : 'Please allow location access';

            return;
        }

        $userPos = new GpsCoordinate($this->userLat, $this->userLng);
        $customerPos = new GpsCoordinate(
            (float) ($this->visit->customer->latitude ?? 0),
            (float) ($this->visit->customer->longitude ?? 0),
        );

        $this->distanceMeters = round($userPos->metersTo($customerPos));
        $this->withinRange = $userPos->within($customerPos, 1500);

        if ($this->withinRange) {
            $this->confirmArrival();
        }
    }

    public function confirmArrival(): void
    {
        if (! $this->withinRange && ! $this->outOfRangeConfirmed) {
            return;
        }

        $this->visit->update([
            'checkin_latitude' => $this->userLat,
            'checkin_longitude' => $this->userLng,
            'checkin_at' => now(),
            'arrival_confirmed' => true,
            'arrival_confirmed_at' => now(),
            'status' => 'open',
        ]);

        $this->step = 'report';
    }

    public function skipGpsAndConfirm(): void
    {
        $this->outOfRangeConfirmed = true;
        $this->confirmArrival();
    }

    public function submitReport(): void
    {
        $this->validate([
            'summary' => 'required|string|min:5',
        ]);

        if ($this->signature) {
            $path = 'signatures/' . $this->visit->id . '_' . time() . '.png';
            $data = explode(',', $this->signature, 2);
            $imgData = base64_decode($data[1] ?? '');
            \Illuminate\Support\Facades\Storage::disk('private')->put($path, $imgData);
        }

        VisitReport::create([
            'visit_id' => $this->visit->id,
            'summary' => $this->summary,
            'customer_feedback' => $this->customerFeedback ?: null,
            'action_taken' => $this->actionTaken ?: null,
            'follow_up_needed' => $this->followUpNeeded,
            'follow_up_note' => $this->followUpNote ?: null,
            'submitted_at' => now(),
        ]);

        $this->visit->update(['status' => 'closed']);

        $this->step = 'done';
    }

    public function render()
    {
        return view('livewire.app.visit-flow', [
            'customer' => $this->visit->customer,
        ]);
    }
}