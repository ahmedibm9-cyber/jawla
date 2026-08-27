<?php

namespace App\Livewire\App;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Tickets extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public string $viewMode = 'table';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $showCreateForm = false;

    public string $newTitle = '';

    public string $newDescription = '';

    public ?int $newCustomerId = null;

    public string $newPriority = 'medium';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'table' ? 'kanban' : 'table';
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;
        $this->reset(['newTitle', 'newDescription', 'newCustomerId', 'newPriority']);
    }

    public function createTicket(): void
    {
        $this->reset(['successMessage', 'errorMessage']);

        $this->validate([
            'newTitle' => 'required|string|max:255',
            'newDescription' => 'required|string',
            'newCustomerId' => 'nullable|exists:customers,id',
            'newPriority' => 'required|in:low,medium,high',
        ]);

        try {
            app(TicketService::class)->create(
                auth()->user()->company_id,
                auth()->id(),
                [
                    'title' => $this->newTitle,
                    'description' => $this->newDescription,
                    'customer_id' => $this->newCustomerId,
                    'priority' => $this->newPriority,
                ]
            );

            $this->successMessage = __('app.ticket_created');
            $this->showCreateForm = false;
            $this->reset(['newTitle', 'newDescription', 'newCustomerId', 'newPriority']);
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.ticket_create_failed');
        }
    }

    public function transitionStatus(int $ticketId, string $newStatus): void
    {
        $this->reset(['successMessage', 'errorMessage']);

        try {
            $ticket = Ticket::where('company_id', auth()->user()->company_id)->findOrFail($ticketId);
            app(TicketService::class)->transitionTo($ticket, $newStatus, auth()->id());
            $this->successMessage = __('app.ticket_updated');
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = __('app.ticket_action_failed');
        }
    }

    public function render(): View
    {
        $query = Ticket::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->with(['customer', 'assignee']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.app.tickets', [
            'tickets' => $query->orderBy('created_at', 'desc')->paginate(20),
        ]);
    }
}
