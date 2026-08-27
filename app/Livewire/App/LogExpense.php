<?php

namespace App\Livewire\App;

use App\Models\CashBox;
use App\Services\ExpenseService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LogExpense extends Component
{
    public string $category = 'other';

    public ?float $amount = null;

    public string $note = '';

    public bool $success = false;

    public string $successMessage = '';

    public string $errorMessage = '';

    public function submit(): void
    {
        $this->validate([
            'category' => 'required|string|in:fuel,maintenance,food,other',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:500',
        ]);

        // Guard against negative cash box balance
        $cashBox = CashBox::where('user_id', auth()->id())->first();
        $balance = $cashBox ? (float) $cashBox->balance : 0;
        if ((float) $this->amount > $balance) {
            $this->errorMessage = app()->getLocale() === 'ar'
                ? 'المبلغ يتجاوز رصيد صندوق النقدية المتاح ('.number_format($balance, 2).')'
                : 'Amount exceeds available cash box balance ('.number_format($balance, 2).')';

            return;
        }

        try {
            $expense = app(ExpenseService::class)->log(
                companyId: auth()->user()->activeCompanyId(),
                userId: auth()->id(),
                category: $this->category,
                amount: (float) $this->amount,
                note: $this->note,
                workSessionId: session('work_session_id'),
            );

            $this->success = true;
            $this->successMessage = __('app.expense_logged').' — '.number_format((float) $expense->amount, 2);

            $this->reset(['category', 'amount', 'note']);
        } catch (\Throwable $e) {
            $this->errorMessage = app()->getLocale() === 'ar'
                ? 'حدث خطأ أثناء تسجيل المصروف: '.$e->getMessage()
                : 'Error logging expense: '.$e->getMessage();
        }
    }

    /**
     * Offline path: the client has already enqueued the expense to the outbox
     * (IndexedDB) and will sync it when back online. Show the queued confirmation
     * and clear the form — no server write happens here.
     */
    public function queueOffline(): void
    {
        $this->success = true;
        $this->successMessage = app()->getLocale() === 'ar'
            ? 'تم حفظ المصروف دون اتصال وستتم مزامنته تلقائيًا عند عودة الاتصال.'
            : 'Expense saved offline — it will sync automatically when you are back online.';

        $this->reset(['category', 'amount', 'note']);
    }

    public function render(): View
    {
        $cashBox = CashBox::where('user_id', auth()->id())->first();
        $cashBoxBalance = $cashBox ? (float) $cashBox->balance : 0;

        return view('livewire.app.log-expense', [
            'cashBoxBalance' => $cashBoxBalance,
        ]);
    }
}
