<?php

namespace App\Services;

use App\Models\CashBox;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function log(int $companyId, int $userId, string $category, float $amount, string $note = '', ?int $workSessionId = null): Expense
    {
        return DB::transaction(function () use ($companyId, $userId, $category, $amount, $note, $workSessionId): Expense {
            // Guard: prevent negative cash box balance
            $cashBox = CashBox::where('user_id', $userId)->lockForUpdate()->first();
            if (! $cashBox) {
                $cashBox = CashBox::create([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'balance' => 0,
                ]);
            }

            if ($amount > (float) $cashBox->balance) {
                throw new \DomainException(
                    app()->getLocale() === 'ar'
                        ? 'المبلغ يتجاوز رصيد صندوق النقدية المتاح'
                        : 'Amount exceeds available cash box balance'
                );
            }

            $expense = Expense::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'work_session_id' => $workSessionId,
                'category' => $category,
                'amount' => $amount,
                'note' => $note,
                'spent_at' => now(),
                'posting_date' => today(),
            ]);

            $cashBox->decrement('balance', $amount);

            return $expense;
        });
    }

    public function cancel(Expense $expense, int $userId): Expense
    {
        return DB::transaction(function () use ($expense, $userId): Expense {
            $cashBox = CashBox::firstOrCreate(
                ['user_id' => $expense->user_id],
                ['company_id' => $expense->company_id, 'balance' => 0],
            );
            $cashBox->increment('balance', (float) $expense->amount);

            $expense->update([
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            return $expense;
        });
    }
}
