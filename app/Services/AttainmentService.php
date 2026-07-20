<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\SalesTarget;
use Illuminate\Support\Carbon;

/**
 * Computes attainment for sales targets: actual booked sales for a rep within a
 * target's period versus the target amount. "Actual" = sum of non-cancelled
 * invoice totals posted inside the window.
 */
class AttainmentService
{
    /** Actual sales_amount for a rep between two dates (inclusive), excluding cancelled invoices. */
    public function actualForRep(int $userId, \DateTimeInterface $from, \DateTimeInterface $to): float
    {
        return (float) Invoice::query()
            ->where('user_id', $userId)
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->whereBetween('posting_date', [
                Carbon::parse($from)->toDateString(),
                Carbon::parse($to)->toDateString(),
            ])
            ->sum('total');
    }

    /**
     * @return array{target: float, actual: float, remaining: float, percent: float}
     */
    public function attainment(SalesTarget $target): array
    {
        $actual = $this->actualForRep($target->user_id, $target->period_start, $target->period_end);
        $targetAmount = (float) $target->target_amount;
        $percent = $targetAmount > 0 ? round($actual / $targetAmount * 100, 1) : 0.0;

        return [
            'target' => $targetAmount,
            'actual' => $actual,
            'remaining' => round(max($targetAmount - $actual, 0), 2),
            'percent' => $percent,
        ];
    }

    /** The rep's target covering today, if any. */
    public function currentTargetForRep(int $userId): ?SalesTarget
    {
        return SalesTarget::query()
            ->where('user_id', $userId)
            ->where('metric', 'sales_amount')
            ->whereDate('period_start', '<=', today())
            ->whereDate('period_end', '>=', today())
            ->latest('period_start')
            ->first();
    }
}
