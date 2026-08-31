<?php

namespace App\Livewire\App;

use App\Models\DailyVisitAssignment;
use App\Models\SyncReceipt;
use App\Models\Task;
use App\Models\Visit;
use App\Models\WorkSession;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Home extends Component
{
    public int $visitCount = 0;

    public ?float $startLat = null;

    public ?float $startLng = null;

    public string $errorMessage = '';

    public string $successMessage = '';

    /** @var array{status: string, label: string, last_sync: string|null} */
    public array $syncStatus = ['status' => 'unknown', 'label' => '', 'last_sync' => null];

    public function mount(): void
    {
        $user = auth()->user();

        $this->visitCount = DailyVisitAssignment::query()
            ->where('user_id', $user->id)
            ->whereDate('visit_date', today())
            ->whereIn('status', ['approved', 'completed'])
            ->count();

        $this->refreshSyncStatus();
    }

    public function refreshSyncStatus(): void
    {
        $userId = auth()->id();
        $companyId = auth()->user()->activeCompanyId();

        $lastSync = SyncReceipt::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->latest('created_at')
            ->value('created_at');

        if ($lastSync === null) {
            $this->syncStatus = [
                'status' => 'warning',
                'label' => __('app.sync_never'),
                'last_sync' => null,
            ];
        } elseif ($lastSync->diffInMinutes(now()) <= 5) {
            $this->syncStatus = [
                'status' => 'ok',
                'label' => __('app.sync_ok'),
                'last_sync' => $lastSync->diffForHumans(),
            ];
        } elseif ($lastSync->diffInMinutes(now()) <= 30) {
            $this->syncStatus = [
                'status' => 'warning',
                'label' => __('app.sync_late'),
                'last_sync' => $lastSync->diffForHumans(),
            ];
        } else {
            $this->syncStatus = [
                'status' => 'error',
                'label' => __('app.sync_stale'),
                'last_sync' => $lastSync->diffForHumans(),
            ];
        }
    }

    public function goToVisit(int $assignmentId): void
    {
        $assignment = DailyVisitAssignment::where('user_id', auth()->id())->findOrFail($assignmentId);
        throw_unless($assignment->status === 'approved', new \DomainException('Only approved assignments can be visited.'));

        $visit = DB::transaction(function () use ($assignment) {
            $existing = Visit::where('user_id', auth()->id())
                ->where('customer_id', $assignment->customer_id)
                ->where('work_session_id', session('work_session_id'))
                ->first();

            if ($existing) {
                return $existing;
            }

            return Visit::create([
                'user_id' => auth()->id(),
                'customer_id' => $assignment->customer_id,
                'work_session_id' => session('work_session_id'),
                'purpose' => 'sale',
                'status' => 'open',
                'route_id' => $assignment->customer->route_id,
                'daily_visit_assignment_id' => $assignment->id,
            ]);
        });

        $this->redirect(route('app.visit', $visit));
    }

    public function startWork(): void
    {
        $this->validate([
            'startLat' => 'nullable|numeric|between:-90,90',
            'startLng' => 'nullable|numeric|between:-180,180',
        ]);

        if (! session('work_session_id')) {
            $session = WorkSession::create([
                'user_id' => auth()->id(),
                'started_at' => now(),
                // GPS populated by frontend via startLat/startLng before calling this
                'start_latitude' => $this->startLat,
                'start_longitude' => $this->startLng,
            ]);
            session(['work_session_id' => $session->id]);
        }

        $this->redirect('/app');
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.app.home', [
            'user' => $user,
            'syncStatus' => $this->syncStatus,
            'todayVisits' => DailyVisitAssignment::query()
                ->where('user_id', $user->id)
                ->whereDate('visit_date', today())
                ->whereIn('status', ['approved', 'completed'])
                ->with('customer')
                ->orderBy('sort_order')
                ->take(100)->get(),
            'pendingCount' => DailyVisitAssignment::query()
                ->where('user_id', $user->id)
                ->whereDate('visit_date', today())
                ->where('status', 'approved')
                ->count(),
            'completedCount' => DailyVisitAssignment::query()
                ->where('user_id', $user->id)
                ->whereDate('visit_date', today())
                ->where('status', 'completed')
                ->count(),
            'openTasks' => Task::query()
                ->with('customer')
                ->where('assigned_to', $user->id)
                ->whereNotIn('status', ['approved', 'cancelled'])
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }
}
