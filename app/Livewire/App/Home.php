<?php

namespace App\Livewire\App;

use App\Models\DailyVisitAssignment;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Home extends Component
{
    public int $visitCount = 0;

    public function mount(): void
    {
        $user = auth()->user();

        $this->visitCount = DailyVisitAssignment::query()
            ->where('user_id', $user->id)
            ->whereDate('visit_date', today())
            ->count();
    }

    public function goToVisit(int $assignmentId): void
    {
        $assignment = DailyVisitAssignment::findOrFail($assignmentId);
        // Find or create today's visit
        $visit = \App\Models\Visit::firstOrCreate([
            'user_id' => auth()->id(),
            'customer_id' => $assignment->customer_id,
            'work_session_id' => session('work_session_id'),
        ], [
            'purpose' => 'sale',
            'status' => 'open',
            'route_id' => $assignment->customer->route_id,
            'daily_visit_assignment_id' => $assignment->id,
        ]);

        $this->redirect(route('app.visit', $visit));
    }

    public function startWork(): void
    {
        if (! session('work_session_id')) {
            $session = \App\Models\WorkSession::create([
                'user_id' => auth()->id(),
                'started_at' => now(),
                'start_latitude' => 0,
                'start_longitude' => 0,
            ]);
            session(['work_session_id' => $session->id]);
        }

        $this->redirect('/app');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.app.home', [
            'user' => $user,
            'todayVisits' => DailyVisitAssignment::query()
                ->where('user_id', $user->id)
                ->whereDate('visit_date', today())
                ->with('customer')
                ->orderBy('sort_order')
                ->get(),
            'pendingCount' => DailyVisitAssignment::query()
                ->where('user_id', $user->id)
                ->whereDate('visit_date', today())
                ->where('status', 'pending')
                ->count(),
            'completedCount' => DailyVisitAssignment::query()
                ->where('user_id', $user->id)
                ->whereDate('visit_date', today())
                ->where('status', 'completed')
                ->count(),
        ]);
    }
}