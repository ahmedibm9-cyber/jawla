<?php

namespace App\Livewire\App;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReturnRecord;
use App\Services\ExpenseService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ReturnService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Global undo toast for reversible rep writes (Phase 6 / B1). Mounted once in the
 * rep layout. After an online sale/payment/return/expense, the flow dispatches
 * 'action-completed' {type,id,message}; this shows a brief toast with an Undo
 * button. Undo reuses the existing transactional cancel() services — a
 * compensating transaction that re-writes stock/cash/balance and logs the
 * reversal — never a delete(). Guarded by ownership (user_id) + not-already-
 * cancelled so a double-click or replay cannot double-reverse.
 *
 * The offline path is deliberately not handled here: an offline write hasn't hit
 * the server yet, so its "undo" is the outbox discard() already exposed by the
 * sync-queue viewer.
 */
class ActionToast extends Component
{
    public ?string $type = null;

    public ?int $id = null;

    public string $message = '';

    public bool $undone = false;

    public string $error = '';

    #[On('action-completed')]
    public function show(string $type, int $id, string $message): void
    {
        $this->reset(['undone', 'error']);
        $this->type = $type;
        $this->id = $id;
        $this->message = $message;
    }

    public function undo(): void
    {
        if ($this->undone || $this->type === null || $this->id === null) {
            return;
        }

        $userId = (int) auth()->id();
        $reason = app()->getLocale() === 'ar' ? 'تراجع بواسطة المندوب' : 'Undone by rep';

        try {
            match ($this->type) {
                'sale' => $this->undoSale($userId, $reason),
                'payment' => $this->undoPayment($userId, $reason),
                'return' => $this->undoReturn($userId, $reason),
                'expense' => $this->undoExpense($userId),
                default => null,
            };
        } catch (\Throwable $e) {
            $this->error = app()->getLocale() === 'ar'
                ? 'تعذّر التراجع عن هذه العملية.'
                : 'This action could not be undone.';

            return;
        }

        $this->undone = true;
        $this->type = null;
        $this->id = null;
        $this->message = '';
    }

    private function undoSale(int $userId, string $reason): void
    {
        $invoice = Invoice::where('user_id', $userId)->whereNull('cancelled_at')->findOrFail($this->id);
        app(InvoiceService::class)->cancel($invoice, $userId, $reason);
    }

    private function undoPayment(int $userId, string $reason): void
    {
        $payment = Payment::where('user_id', $userId)->whereNull('cancelled_at')->findOrFail($this->id);
        app(PaymentService::class)->cancel($payment, $userId, $reason);
    }

    private function undoReturn(int $userId, string $reason): void
    {
        $return = ReturnRecord::where('user_id', $userId)->whereNull('cancelled_at')->findOrFail($this->id);
        app(ReturnService::class)->cancel($return, $userId, $reason);
    }

    private function undoExpense(int $userId): void
    {
        $expense = Expense::where('user_id', $userId)->whereNull('cancelled_at')->findOrFail($this->id);
        app(ExpenseService::class)->cancel($expense, $userId);
    }

    public function dismiss(): void
    {
        $this->reset(['type', 'id', 'message', 'undone']);
        // Preserve error until explicitly acknowledged
    }

    public function render()
    {
        return view('livewire.app.action-toast');
    }
}
