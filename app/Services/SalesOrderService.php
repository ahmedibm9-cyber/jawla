<?php

namespace App\Services;

use App\Enums\ApprovalRequestStatus;
use App\Models\ApprovalRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Contracts\DocumentNumberService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly WorkflowApproverResolver $approverResolver,
        private readonly DocumentNumberService $numbers,
    ) {}

    /** @param list<array{product_id:int, quantity:float|int|string, unit_price:float|int|string}> $items */
    public function createAndSubmit(User $rep, int $customerId, array $items, array $attributes = []): SalesOrder
    {
        return DB::transaction(function () use ($rep, $customerId, $items, $attributes): SalesOrder {
            throw_unless($rep->can('sales_orders.create'), new AuthorizationException('You cannot create sales orders.'));
            throw_if($items === [], new \DomainException('A sales order requires at least one item.'));
            $companyId = $rep->activeCompanyId();
            $customer = Customer::query()->whereKey($customerId)->where('status', 'approved')->where('is_active', true)->firstOrFail();
            $outletId = $attributes['customer_outlet_id'] ?? null;
            if ($outletId !== null) {
                $customer->outlets()->findOrFail((int) $outletId);
            }

            $order = SalesOrder::create([
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'customer_outlet_id' => $outletId,
                'user_id' => $rep->id,
                'order_number' => $this->numbers->generate('sales_order', $companyId),
                'status' => 'draft',
                'requested_delivery_date' => $attributes['requested_delivery_date'] ?? null,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $total = '0.00';
            foreach ($items as $input) {
                $product = Product::query()->findOrFail((int) $input['product_id']);
                $quantity = number_format((float) $input['quantity'], 3, '.', '');
                $price = number_format((float) $input['unit_price'], 2, '.', '');
                throw_if(bccomp($quantity, '0.000', 3) <= 0 || bccomp($price, '0.00', 2) < 0, new \DomainException(
                    'Order quantities must be positive and prices cannot be negative.',
                ));
                $lineTotal = bcmul($quantity, $price, 2);
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                ]);
                $total = bcadd($total, $lineTotal, 2);
            }

            $order->update(['subtotal' => $total, 'total' => $total]);
            $approver = $this->approverResolver->forCompany($companyId);
            $this->approvals->submit($order, $rep, [$approver]);
            $order->update(['status' => 'submitted', 'submitted_at' => now()]);

            return $order->fresh(['items', 'approvals.steps']);
        });
    }

    public function approve(ApprovalRequest $request, User $actor): SalesOrder
    {
        throw_unless($actor->can('sales_orders.approve'), new AuthorizationException('You cannot approve sales orders.'));

        return DB::transaction(function () use ($request, $actor): SalesOrder {
            $request = $this->approvals->approve($request, $actor);
            throw_unless($request->approvable instanceof SalesOrder, new \InvalidArgumentException('Not a sales order approval.'));
            $order = $request->approvable;
            if ($request->status === ApprovalRequestStatus::Approved) {
                $order->update(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now()]);
                DB::afterCommit(fn () => app(WebhookService::class)->dispatch((int) $order->company_id, 'sales_order.approved', [
                    'sales_order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_id' => $order->customer_id,
                    'total' => $order->total,
                ]));
            }

            return $order->fresh();
        });
    }

    public function reject(ApprovalRequest $request, User $actor, string $reason): SalesOrder
    {
        throw_unless($actor->can('sales_orders.approve'), new AuthorizationException('You cannot reject sales orders.'));
        $reason = trim($reason);
        throw_if($reason === '', new \DomainException('A rejection reason is required.'));

        return DB::transaction(function () use ($request, $actor, $reason): SalesOrder {
            $request = $this->approvals->reject($request, $actor, $reason);
            throw_unless($request->approvable instanceof SalesOrder, new \InvalidArgumentException('Not a sales order approval.'));
            $request->approvable->update(['status' => 'rejected', 'decision_reason' => $reason]);

            return $request->approvable->fresh();
        });
    }
}
