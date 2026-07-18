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

            $cashBox = CashBox::firstOrCreate(
                ['user_id' => $userId],
                ['company_id' => $companyId, 'balance' => 0],
            );
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
