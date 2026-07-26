<?php

namespace App\Services\Sync\Handlers;

use App\Models\User;
use App\Services\ExpenseService;

/**
 * Offline expense → ExpenseService::log (cash box deduction), applied exactly
 * once on sync.
 */
class ExpenseSyncHandler extends AbstractRepWriteHandler
{
    public function __construct(private readonly ExpenseService $expenses) {}

    public function type(): string
    {
        return 'expense';
    }

    public function handle(User $rep, array $payload): array
    {
        $data = $this->validated($payload, [
            'category' => ['required', 'string', 'in:fuel,maintenance,food,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:500'],
            'work_session_id' => ['nullable', 'integer'],
        ]);

        $expense = $this->expenses->log(
            companyId: $rep->activeCompanyId(),
            userId: $rep->id,
            category: $data['category'],
            amount: (float) $data['amount'],
            note: $data['note'] ?? '',
            workSessionId: isset($data['work_session_id']) ? (int) $data['work_session_id'] : null,
        );

        return ['expense_id' => $expense->id];
    }
}
