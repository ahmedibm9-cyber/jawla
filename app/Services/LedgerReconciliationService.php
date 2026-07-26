<?php

namespace App\Services;

use App\Models\CashBox;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Support\ActiveCompanyContext;

class LedgerReconciliationService
{
    public function report(int $companyId): array
    {
        app(ActiveCompanyContext::class)->assertMatches($companyId);
        $drift = ['customers' => [], 'cash_boxes' => [], 'stocks' => []];

        Customer::withoutGlobalScopes()->where('company_id', $companyId)
            ->select(['id', 'balance'])->chunkById(200, function ($customers) use (&$drift, $companyId): void {
                foreach ($customers as $customer) {
                    $expected = (string) Invoice::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->where('customer_id', $customer->id)
                        ->whereNotIn('status', ['draft', 'voided', 'cancelled'])
                        ->sum('remaining_amount');
                    $this->record($drift['customers'], $customer->id, (string) $customer->balance, $expected, 2);
                }
            });

        CashBox::withoutGlobalScopes()->where('company_id', $companyId)
            ->select(['id', 'user_id', 'balance'])->chunkById(200, function ($boxes) use (&$drift, $companyId): void {
                foreach ($boxes as $box) {
                    $payments = (string) Payment::withoutGlobalScopes()
                        ->where('company_id', $companyId)->where('user_id', $box->user_id)
                        ->where('method', 'cash')->whereNull('cancelled_at')->sum('amount');
                    $expenses = (string) Expense::withoutGlobalScopes()
                        ->where('company_id', $companyId)->where('user_id', $box->user_id)
                        ->whereNull('cancelled_at')->sum('amount');
                    $refunds = (string) Refund::withoutGlobalScopes()
                        ->where('company_id', $companyId)->where('cash_box_id', $box->id)
                        ->where('method', 'cash')->where('status', 'completed')->sum('amount');
                    $expected = bcsub(bcsub($payments, $expenses, 2), $refunds, 2);
                    $this->record($drift['cash_boxes'], $box->id, (string) $box->balance, $expected, 2);
                }
            });

        Stock::query()->whereHas('warehouse', fn ($query) => $query->where('company_id', $companyId))
            ->select(['id', 'warehouse_id', 'product_id', 'batch_id', 'quantity'])
            ->chunkById(200, function ($stocks) use (&$drift): void {
                foreach ($stocks as $stock) {
                    $expected = (string) StockMovement::query()
                        ->where('warehouse_id', $stock->warehouse_id)
                        ->where('product_id', $stock->product_id)
                        ->where('batch_id', $stock->batch_id)
                        ->sum('quantity_change');
                    $this->record($drift['stocks'], $stock->id, (string) $stock->quantity, $expected, 3);
                }
            });

        return $drift;
    }

    private function record(array &$rows, int $id, string $stored, string $expected, int $scale): void
    {
        if (bccomp($stored, $expected, $scale) !== 0) {
            $rows[] = [
                'id' => $id,
                'stored' => number_format((float) $stored, $scale, '.', ''),
                'ledger' => number_format((float) $expected, $scale, '.', ''),
                'difference' => bcsub($stored, $expected, $scale),
            ];
        }
    }
}
