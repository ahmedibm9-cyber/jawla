<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Notifications\PurchaseOfferOutcome;
use App\Services\Contracts\DocumentNumberService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * B7 dual-review lifecycle (decision D-04): Sales reviews first; Purchasing
 * only sees offers Sales approved. A rejection never kills the offer — the
 * rep can edit and resubmit it, which restarts the review from Sales.
 */
class PurchaseRequestService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
    ) {}

    public function salesApprove(PurchaseRequest $request, int $reviewerId, ?string $notes = null): PurchaseRequest
    {
        return DB::transaction(function () use ($request, $reviewerId, $notes): PurchaseRequest {
            $request = $this->lockedInStatus($request, ['pending']);
            $this->assertNotExpired($request);

            $request->update([
                'status' => 'sales_approved',
                'sales_reviewed_by' => $reviewerId,
                'sales_reviewed_at' => now(),
                'sales_review_notes' => $notes,
            ]);

            Activity::log('purchase_offer_sales_approved', $request, "Purchase offer #{$request->id} approved by Sales");
            $this->notifyRep($request, 'sales_approved', $notes);

            return $request;
        });
    }

    public function salesReject(PurchaseRequest $request, int $reviewerId, ?string $notes = null): PurchaseRequest
    {
        return DB::transaction(function () use ($request, $reviewerId, $notes): PurchaseRequest {
            $request = $this->lockedInStatus($request, ['pending']);

            $request->update([
                'status' => 'rejected_by_sales',
                'sales_reviewed_by' => $reviewerId,
                'sales_reviewed_at' => now(),
                'sales_review_notes' => $notes,
            ]);

            Activity::log('purchase_offer_sales_rejected', $request, "Purchase offer #{$request->id} rejected by Sales");
            $this->notifyRep($request, 'rejected_by_sales', $notes);

            return $request;
        });
    }

    public function purchasingApprove(PurchaseRequest $request, int $reviewerId, ?string $notes = null): PurchaseOrder
    {
        return DB::transaction(function () use ($request, $reviewerId, $notes): PurchaseOrder {
            $request = $this->lockedInStatus($request, ['sales_approved']);
            $this->assertNotExpired($request);

            throw_if($request->supplier_id === null, new \RuntimeException(
                'A supplier must be set on the offer before a purchase order can be generated.'));

            $lineTotal = round((float) $request->quantity * (float) $request->offered_price, 2);

            $order = PurchaseOrder::create([
                'company_id' => $request->company_id,
                'supplier_id' => $request->supplier_id,
                'order_number' => $this->numbers->generate('purchase_order', $request->company_id),
                'status' => 'draft',
                'order_date' => now()->toDateString(),
                'payment_terms' => $request->payment_terms,
                'currency' => $request->currency,
                'subtotal' => $lineTotal,
                'shipping_cost' => 0,
                'total' => $lineTotal,
                'notes' => "Generated from purchase offer #{$request->id}",
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->offered_price,
                'line_total' => $lineTotal,
            ]);

            $request->update([
                'status' => 'purchasing_approved',
                'purchasing_reviewed_by' => $reviewerId,
                'purchasing_reviewed_at' => now(),
                'purchasing_review_notes' => $notes,
                'purchase_order_id' => $order->id,
            ]);

            Activity::log('purchase_order_created', $order, "PO {$order->order_number} generated from purchase offer #{$request->id}");
            $this->notifyRep($request, 'purchasing_approved', $notes, $order->order_number);

            return $order;
        });
    }

    public function purchasingReject(PurchaseRequest $request, int $reviewerId, ?string $notes = null): PurchaseRequest
    {
        return DB::transaction(function () use ($request, $reviewerId, $notes): PurchaseRequest {
            $request = $this->lockedInStatus($request, ['sales_approved']);

            $request->update([
                'status' => 'rejected_by_purchasing',
                'purchasing_reviewed_by' => $reviewerId,
                'purchasing_reviewed_at' => now(),
                'purchasing_review_notes' => $notes,
            ]);

            Activity::log('purchase_offer_purchasing_rejected', $request, "Purchase offer #{$request->id} rejected by Purchasing");
            $this->notifyRep($request, 'rejected_by_purchasing', $notes);

            return $request;
        });
    }

    /**
     * Rep edits a rejected offer and sends it back to the start of the
     * review chain, clearing both departments' previous decisions.
     */
    public function resubmit(PurchaseRequest $request, int $repId, array $attributes = []): PurchaseRequest
    {
        return DB::transaction(function () use ($request, $repId, $attributes): PurchaseRequest {
            $fresh = PurchaseRequest::whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            throw_if($fresh->user_id !== $repId, new \RuntimeException(
                'Only the rep who submitted this offer can resubmit it.'));
            throw_if(! in_array($fresh->status, ['rejected_by_sales', 'rejected_by_purchasing'], true),
                new \RuntimeException('Only rejected offers can be edited and resubmitted.'));

            $fresh->fill(Arr::only($attributes, [
                'product_id', 'supplier_id', 'quantity', 'offered_price',
                'currency', 'payment_terms', 'expires_at',
            ]));
            $fresh->fill([
                'status' => 'pending',
                'sales_reviewed_by' => null,
                'sales_reviewed_at' => null,
                'sales_review_notes' => null,
                'purchasing_reviewed_by' => null,
                'purchasing_reviewed_at' => null,
                'purchasing_review_notes' => null,
            ]);
            $fresh->save();

            Activity::log('purchase_offer_resubmitted', $fresh, "Purchase offer #{$fresh->id} edited and resubmitted");

            return $fresh;
        });
    }

    private function lockedInStatus(PurchaseRequest $request, array $expected): PurchaseRequest
    {
        $fresh = PurchaseRequest::whereKey($request->getKey())->lockForUpdate()->firstOrFail();

        throw_if(! in_array($fresh->status, $expected, true), new \RuntimeException(
            "Offer is not awaiting this decision (status: {$fresh->status})."));

        return $fresh;
    }

    private function assertNotExpired(PurchaseRequest $request): void
    {
        throw_if($request->isExpired(), new \RuntimeException(
            'This offer has expired and can no longer be approved.'));
    }

    // afterCommit: a rolled-back decision must never notify the rep.
    private function notifyRep(PurchaseRequest $request, string $outcome, ?string $reason = null, ?string $poNumber = null): void
    {
        DB::afterCommit(function () use ($request, $outcome, $reason, $poNumber): void {
            $request->user?->notify(new PurchaseOfferOutcome($request, $outcome, $reason, $poNumber));
        });
    }
}
