<?php

namespace App\Livewire\App;

use App\Models\CashBox;
use App\Models\CashReconciliation;
use App\Services\CashReconciliationService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CashReconcile extends Component
{
    public ?float $counted_amount = null;

    public string $notes = '';

    public bool $success = false;

    public string $successMessage = '';

    public function submit(): void
    {
        $this->validate([
            'counted_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $reconciliation = app(CashReconciliationService::class)->submit(
            companyId: auth()->user()->company_id,
            userId: auth()->id(),
            countedAmount: (float) $this->counted_amount,
            notes: $this->notes,
            workSessionId: session('work_session_id'),
        );

        $variance = (float) $reconciliation->variance;
        $this->success = true;
        $this->successMessage = $variance === 0.0
            ? __('app.recon_balanced')
            : __('app.recon_variance_recorded', ['variance' => number_format($variance, 2)]);

        $this->reset(['counted_amount', 'notes']);
    }

    public function render()
    {
        $cashBox = CashBox::where('user_id', auth()->id())->first();

        return view('livewire.app.cash-reconcile', [
            'expectedBalance' => $cashBox ? (float) $cashBox->balance : 0.0,
            'history' => CashReconciliation::where('user_id', auth()->id())
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
