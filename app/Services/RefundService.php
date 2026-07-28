<?php

namespace App\Services;

use App\Exceptions\Domain\DomainException;
use App\Models\CashBox;
use App\Models\CustomerCredit;
use App\Models\Refund;
use App\Models\User;
use App\Services\Contracts\DocumentNumberService;
use App\Support\ActiveCompanyContext;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function request(
        int $companyId,
        int $requestedBy,
        int $customerCreditId,
        string $amount,
        string $method,
        string $reason,
        string $intentId,
    ): Refund {
        app(ActiveCompanyContext::class)->assertMatches($companyId);
        $money = $this->money($amount);
        $reason = trim($reason);
        $intentId = trim($intentId);

        if (! in_array($method, ['cash', 'bank', 'card'], true) || $reason === '' || $intentId === '') {
            throw new DomainException('Refund method, reason, and intent ID are required.');
        }

        return DB::transaction(function () use (
            $companyId,
            $requestedBy,
            $customerCreditId,
            $money,
            $method,
            $reason,
            $intentId,
        ): Refund {
            $requester = User::withoutGlobalScopes()->whereKey($requestedBy)->lockForUpdate()->firstOrFail();
            if (! $requester->hasCompanyAccess($companyId)
                || ! $requester->can('refunds.request')) {
                throw new DomainException('Only an authorized sales user may request a refund.');
            }

            $existing = Refund::where('company_id', $companyId)
                ->where('intent_id', $intentId)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                if ((int) $existing->customer_credit_id !== $customerCreditId
                    || $existing->amount !== $money
                    || $existing->method !== $method
                    || $existing->reason !== $reason) {
                    throw new DomainException('Refund intent was already used with a different payload.');
                }

                return $existing;
            }

            $credit = CustomerCredit::whereKey($customerCreditId)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();
            if (! $credit || $credit->status !== 'available'
                || bccomp($money, (string) $credit->remaining_amount, 2) > 0) {
                throw new DomainException('Refund exceeds the available customer credit.');
            }

            $cashBoxId = null;
            if ($method === 'cash') {
                $cashBoxId = CashBox::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('user_id', $requestedBy)
                    ->value('id');
                if ($cashBoxId === null) {
                    throw new DomainException('A company cash box is required for a cash refund.');
                }
            }

            return Refund::create([
                'company_id' => $companyId,
                'customer_id' => $credit->customer_id,
                'customer_credit_id' => $credit->id,
                'cash_box_id' => $cashBoxId,
                'requested_by' => $requestedBy,
                'refund_number' => app(DocumentNumberService::class)->generate('refund', $companyId),
                'intent_id' => $intentId,
                'method' => $method,
                'amount' => $money,
                'status' => 'pending_approval',
                'reason' => $reason,
            ]);
        });
    }

    public function approve(Refund $refund, int $managerId): Refund
    {
        return DB::transaction(function () use ($refund, $managerId): Refund {
            $refund = Refund::whereKey($refund->id)->lockForUpdate()->firstOrFail();
            $this->assertManager($managerId, (int) $refund->company_id);

            if (in_array($refund->status, ['completed', 'pending_external'], true)) {
                return $refund;
            }
            if ($refund->status !== 'pending_approval') {
                throw new DomainException('Only a pending refund may be approved.');
            }

            $credit = CustomerCredit::whereKey($refund->customer_credit_id)
                ->where('company_id', $refund->company_id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($credit->status !== 'available'
                || bccomp((string) $refund->amount, (string) $credit->remaining_amount, 2) > 0) {
                throw new DomainException('Refund exceeds the available customer credit.');
            }

            $completedAt = null;
            $status = 'pending_external';
            if ($refund->method === 'cash') {
                $cashBox = CashBox::withoutGlobalScopes()
                    ->whereKey($refund->cash_box_id)
                    ->where('company_id', $refund->company_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if (bccomp((string) $cashBox->balance, (string) $refund->amount, 2) < 0) {
                    throw new DomainException('Cash-box balance is insufficient for this refund.');
                }
                $cashBox->balance = bcsub((string) $cashBox->balance, (string) $refund->amount, 2);
                $cashBox->save();
                $status = 'completed';
                $completedAt = now();
            }

            $credit->remaining_amount = bcsub(
                (string) $credit->remaining_amount,
                (string) $refund->amount,
                2,
            );
            $credit->status = bccomp((string) $credit->remaining_amount, '0.00', 2) === 0
                ? 'used'
                : 'available';
            $credit->save();

            $refund->update([
                'approved_by' => $managerId,
                'approved_at' => now(),
                'status' => $status,
                'completed_at' => $completedAt,
            ]);

            return $refund->fresh();
        });
    }

    public function confirmExternal(Refund $refund, int $managerId, string $externalReference): Refund
    {
        return DB::transaction(function () use ($refund, $managerId, $externalReference): Refund {
            $refund = Refund::whereKey($refund->id)->lockForUpdate()->firstOrFail();
            $this->assertManager($managerId, (int) $refund->company_id);
            $externalReference = trim($externalReference);

            if ($refund->method === 'cash' || $externalReference === '') {
                throw new DomainException('A bank or card refund requires an external confirmation reference.');
            }
            if ($refund->status === 'completed') {
                if ($refund->external_reference !== $externalReference) {
                    throw new DomainException('Refund was already confirmed with another external reference.');
                }

                return $refund;
            }
            if ($refund->status !== 'pending_external') {
                throw new DomainException('Only an approved external refund may be confirmed.');
            }

            $refund->update([
                'external_reference' => $externalReference,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $refund->fresh();
        });
    }

    private function assertManager(int $managerId, int $companyId): void
    {
        $manager = User::withoutGlobalScopes()->whereKey($managerId)->lockForUpdate()->firstOrFail();
        if (! $manager->hasCompanyAccess($companyId) || ! $manager->can('refunds.approve')) {
            throw new DomainException('A same-company sales manager must approve the refund.');
        }
    }

    private function money(string $amount): string
    {
        if (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $amount)) {
            throw new DomainException('Refund amount must be a positive currency value.');
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $money = ltrim($whole, '0').'.'.str_pad($fraction, 2, '0');
        if (str_starts_with($money, '.')) {
            $money = '0'.$money;
        }
        if (bccomp($money, '0.00', 2) <= 0) {
            throw new DomainException('Refund amount must be greater than zero.');
        }

        return $money;
    }
}
