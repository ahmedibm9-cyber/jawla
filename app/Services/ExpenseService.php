<?php

namespace App\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\CashBox;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function log(int $companyId, int $userId, string $category, float $amount, string $note = '', ?int $workSessionId = null): Expense
    {
        if ($amount <= 0) {
            throw new DomainException(
                app()->getLocale() === 'ar'
                    ? 'المبلغ يجب أن يكون أكبر من صفر'
                    : 'Amount must be greater than zero'
            );
        }

        return DB::transaction(function () use ($companyId, $userId, $category, $amount, $note, $workSessionId): Expense {
            // Guard: prevent negative cash box balance
            $cashBox = $this->ensureCashBox($userId, $companyId);

            if ($amount > (float) $cashBox->balance) {
                throw new DomainException(
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
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            if ($expense->cancelled_at !== null) {
                return $expense;
            }

            $cashBox = $this->ensureCashBox($expense->user_id, $expense->company_id);
            $cashBox->increment('balance', (float) $expense->amount);

            $expense->update([
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            return $expense;
        });
    }

    private function ensureCashBox(int $userId, int $companyId): CashBox
    {
        $user = User::withoutGlobalScopes()->whereKey($userId)->lockForUpdate()->firstOrFail();
        if ($user->company_id !== $companyId) {
            throw new DomainException('Cash box user does not belong to this company.');
        }

        $cashBox = CashBox::withoutGlobalScopes()->where('user_id', $userId)->lockForUpdate()->first();
        if (! $cashBox) {
            $cashBox = CashBox::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'balance' => 0,
            ]);
        } elseif ($cashBox->company_id === null) {
            $cashBox->update(['company_id' => $companyId]);
        }

        return $cashBox->refresh();
    }
}
