<?php

namespace App\Services;

use App\Models\DailyVisitAssignment;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class RoiService
{
    public function repRoi(int $userId, Carbon $from, Carbon $to): array
    {
        $visitQuery = DailyVisitAssignment::query()
            ->where('user_id', $userId)
            ->whereBetween('visit_date', [$from->startOfDay(), $to->endOfDay()]);

        $assigned = (clone $visitQuery)->count();
        $completed = (clone $visitQuery)->where('status', 'completed')->count();

        $invoiceQuery = Invoice::query()
            ->where('user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('posting_date', [$from->startOfDay(), $to->endOfDay()]);

        $sales = (clone $invoiceQuery)->sum('total');

        $paymentQuery = Payment::query()
            ->where('user_id', $userId)
            ->whereNull('cancelled_at')
            ->whereBetween('collected_at', [$from->startOfDay(), $to->endOfDay()]);

        $collected = (clone $paymentQuery)->sum('amount');

        return [
            'assigned' => $assigned,
            'completed' => $completed,
            'completion_rate' => $assigned > 0 ? round(($completed / $assigned) * 100, 1) : 0,
            'sales' => round((float) $sales, 2),
            'collected' => round((float) $collected, 2),
            'collection_rate' => $sales > 0 ? round((($collected / $sales) * 100), 1) : 0,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }
}
