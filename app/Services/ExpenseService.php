<?php

namespace App\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\Activity;
use App\Models\CashBox;
use App\Models\Expense;
use App\Models\User;
use App\Support\ActiveCompanyContext;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    private const APPROVAL_THRESHOLD = 500;

    public function log(int $companyId, int $userId, string $category, float $amount, string $note = '', ?int $workSessionId = null): Expense
    {
        app(ActiveCompanyContext::class)->assertMatches($companyId);

        if ($amount <= 0) {
            throw new DomainException(
                app()->getLocale() === 'ar'
                    ? 'المبلغ يجب أن يكون أكبر من صفر'
                    : 'Amount must be greater than zero'
            );
        }

        $requiresApproval = $amount >= self::APPROVAL_THRESHOLD;

        return DB::transaction(function () use ($companyId, $userId, $category, $amount, $note, $workSessionId, $requiresApproval): Expense {
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
                'status' => $requiresApproval ? 'pending' : 'approved',
                'approved_at' => $requiresApproval ? null : now(),
                'approved_by' => $requiresApproval ? null : $userId,
            ]);

            if (! $requiresApproval) {
                $cashBox->decrement('balance', $amount);
            }

            return $expense;
        });
    }

    public function approve(Expense $expense, int $managerId): Expense
    {
        $manager = User::withoutGlobalScopes()->findOrFail($managerId);
        if (! $manager->can('expenses.approve') && ! $manager->can('update:expense')) {
            throw new DomainException(
                app()->getLocale() === 'ar'
                    ? 'لا تملك صلاحية اعتماد هذا المصروف'
                    : 'You are not authorized to approve this expense.'
            );
        }

        return DB::transaction(function () use ($expense, $managerId): Expense {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if ($expense->status !== 'pending') {
                throw new DomainException(
                    app()->getLocale() === 'ar'
                        ? 'هذا المصروف ليس معلقاً'
                        : 'This expense is not pending.'
                );
            }

            $cashBox = $this->ensureCashBox($expense->user_id, $expense->company_id);
            $cashBox->decrement('balance', (float) $expense->amount);

            // AppendOnly blocks Eloquent update — use raw query for status change
            DB::table('expenses')->where('id', $expense->id)->update([
                'status' => 'approved',
                'approved_by' => $managerId,
                'approved_at' => now(),
            ]);

            Activity::log('expense_approved', $expense, "Approved expense #{$expense->id}: {$expense->amount}");

            return $expense->refresh();
        });
    }

    public function reject(Expense $expense, int $managerId): Expense
    {
        $manager = User::withoutGlobalScopes()->findOrFail($managerId);
        if (! $manager->can('expenses.reject') && ! $manager->can('update:expense')) {
            throw new DomainException(
                app()->getLocale() === 'ar'
                    ? 'لا تملك صلاحية رفض هذا المصروف'
                    : 'You are not authorized to reject this expense.'
            );
        }

        return DB::transaction(function () use ($expense, $managerId): Expense {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if ($expense->status !== 'pending') {
                throw new DomainException(
                    app()->getLocale() === 'ar'
                        ? 'هذا المصروف ليس معلقاً'
                        : 'This expense is not pending.'
                );
            }

            DB::table('expenses')->where('id', $expense->id)->update([
                'status' => 'rejected',
                'approved_by' => $managerId,
                'approved_at' => now(),
            ]);

            Activity::log('expense_rejected', $expense, "Rejected expense #{$expense->id}: {$expense->amount}");

            return $expense->refresh();
        });
    }

    public function cancel(Expense $expense, int $userId): Expense
    {
        $actor = User::withoutGlobalScopes()->findOrFail($userId);
        $isOwner = (int) $expense->user_id === (int) $userId;
        $isManager = $actor->can('expenses.cancel') || $actor->can('update:expense');
        if (! $isOwner && ! $isManager) {
            throw new DomainException(
                app()->getLocale() === 'ar'
                    ? 'لا تملك صلاحية إلغاء هذا المصروف'
                    : 'You are not authorized to cancel this expense.'
            );
        }

        return DB::transaction(function () use ($expense, $userId): Expense {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            if ($expense->cancelled_at !== null) {
                return $expense;
            }

            // Pending expenses have not deducted cash yet — just mark rejected
            if ($expense->status === 'pending') {
                DB::table('expenses')->where('id', $expense->id)->update([
                    'status' => 'rejected',
                    'cancelled_at' => now(),
                    'cancelled_by' => $userId,
                ]);

                return $expense->refresh();
            }

            $cashBox = $this->ensureCashBox($expense->user_id, $expense->company_id);
            $cashBox->increment('balance', (float) $expense->amount);

            DB::table('expenses')->where('id', $expense->id)->update([
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            return $expense->refresh();
        });
    }

    private function ensureCashBox(int $userId, int $companyId): CashBox
    {
        $user = User::withoutGlobalScopes()->whereKey($userId)->lockForUpdate()->firstOrFail();
        if (! $user->hasCompanyAccess($companyId)) {
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
