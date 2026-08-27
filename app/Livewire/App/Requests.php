<?php

namespace App\Livewire\App;

use App\Models\Request;
use App\Services\RequestService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Requests extends Component
{
    use WithPagination;

    public string $statusFilter = 'new';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $showCreateForm = false;

    public string $newType = 'other';

    public string $newTitle = '';

    public string $newDescription = '';

    public string $viewMode = 'table';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;
        $this->reset(['newType', 'newTitle', 'newDescription']);
    }

    public function toggleView(): void
    {
        $this->viewMode = $this->viewMode === 'table' ? 'kanban' : 'table';
    }

    public function createRequest(): void
    {
        $this->reset(['successMessage', 'errorMessage']);

        $this->validate([
            'newType' => 'required|in:discount,leave,price_override,other',
            'newTitle' => 'required|string|max:255',
            'newDescription' => 'required|string|max:2000',
        ]);

        try {
            app(RequestService::class)->create([
                'company_id' => auth()->user()->activeCompanyId(),
                'user_id' => auth()->id(),
                'type' => $this->newType,
                'title' => $this->newTitle,
                'description' => $this->newDescription,
            ]);

            $this->successMessage = __('app.request_created');
            $this->showCreateForm = false;
            $this->reset(['newType', 'newTitle', 'newDescription']);
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.request_create_failed');
        }
    }

    public function approveRequest(int $requestId): void
    {
        $this->reset(['successMessage', 'errorMessage']);

        try {
            $request = Request::where('company_id', auth()->user()->activeCompanyId())->findOrFail($requestId);
            app(RequestService::class)->approve($request, auth()->user());
            $this->successMessage = __('app.request_approved');
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.request_action_failed');
        }
    }

    public function rejectRequest(int $requestId): void
    {
        $this->reset(['successMessage', 'errorMessage']);

        try {
            $request = Request::where('company_id', auth()->user()->activeCompanyId())->findOrFail($requestId);
            app(RequestService::class)->reject($request, auth()->user(), 'Rejected by manager');
            $this->successMessage = __('app.request_rejected');
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.request_action_failed');
        }
    }

    public function render(): View
    {
        $companyId = auth()->user()->activeCompanyId();

        $query = Request::where('company_id', $companyId)
            ->where('is_active', true);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $requests = $query->latest()->paginate(20);

        return view('livewire.app.requests', [
            'requests' => $requests,
        ]);
    }
}
