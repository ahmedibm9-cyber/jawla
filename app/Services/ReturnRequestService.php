<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Contracts\DocumentNumberService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ReturnRequestService
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly WorkflowApproverResolver $approverResolver,
        private readonly DocumentNumberService $numbers,
        private readonly ReturnService $returns,
    ) {}

    /** @param list<array{invoice_item_id:int, quantity:float|int|string, condition:string}> $items */
    public function submit(User $rep, int $customerId, int $invoiceId, array $items, string $reason, ?int $visitId = null): ReturnRequest
    {
        return DB::transaction(function () use ($rep, $customerId, $invoiceId, $items, $reason, $visitId): ReturnRequest {
            throw_unless($rep->can('returns.create'), new AuthorizationException('You cannot submit returns.'));
            throw_if($items === [], new \DomainException('A return request requires at least one item.'));
            $reason = trim($reason);
            throw_if($reason === '', new \DomainException('A return reason is required.'));
            $companyId = $rep->activeCompanyId();
            $invoice = Invoice::query()->whereKey($invoiceId)->where('customer_id', $customerId)->firstOrFail();

            $request = ReturnRequest::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'user_id' => $rep->id,
                'visit_id' => $visitId,
                'against_invoice_id' => $invoice->id,
                'request_number' => $this->numbers->generate('return_request', $companyId),
                'status' => 'pending_approval',
                'reason' => $reason,
                'submitted_at' => now(),
            ]);

            $total = '0.00';
            $seen = [];
            foreach ($items as $input) {
                $invoiceItemId = (int) $input['invoice_item_id'];
                throw_if(isset($seen[$invoiceItemId]), new \DomainException('An invoice line may appear only once.'));
                $seen[$invoiceItemId] = true;
                $line = InvoiceItem::query()->whereKey($invoiceItemId)->where('invoice_id', $invoice->id)->firstOrFail();
                $quantity = number_format((float) $input['quantity'], 3, '.', '');
                throw_if(bccomp($quantity, '0.000', 3) <= 0 || bccomp($quantity, (string) $line->quantity, 3) > 0, new \DomainException(
                    'Return quantity must be positive and cannot exceed the invoiced quantity.',
                ));
                $condition = (string) $input['condition'];
                throw_unless(in_array($condition, ['sellable', 'damaged'], true), new \DomainException('Invalid return condition.'));
                $lineTotal = bcmul($quantity, (string) $line->unit_price, 2);
                $request->items()->create([
                    'invoice_item_id' => $line->id,
                    'quantity' => $quantity,
                    'condition' => $condition,
                    'line_total' => $lineTotal,
                ]);
                $total = bcadd($total, $lineTotal, 2);
            }

            $request->update(['total' => $total]);
            $this->approvals->submit($request, $rep, [$this->approverResolver->forCompany($companyId)]);

            return $request->fresh(['items', 'approvals.steps']);
        });
    }

    public function approve(ApprovalRequest $approval, User $actor): ReturnRequest
    {
        throw_unless($actor->can('return_requests.approve'), new AuthorizationException('You cannot approve return requests.'));

        return DB::transaction(function () use ($approval, $actor): ReturnRequest {
            $approval = $this->approvals->approve($approval, $actor);
            throw_unless($approval->approvable instanceof ReturnRequest, new \InvalidArgumentException('Not a return request approval.'));
            $request = $approval->approvable;
            $request->update(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now()]);

            return $request->fresh();
        });
    }

    public function reject(ApprovalRequest $approval, User $actor, string $reason): ReturnRequest
    {
        throw_unless($actor->can('return_requests.approve'), new AuthorizationException('You cannot reject return requests.'));
        $reason = trim($reason);
        throw_if($reason === '', new \DomainException('A rejection reason is required.'));

        return DB::transaction(function () use ($approval, $actor, $reason): ReturnRequest {
            $approval = $this->approvals->reject($approval, $actor, $reason);
            throw_unless($approval->approvable instanceof ReturnRequest, new \InvalidArgumentException('Not a return request approval.'));
            $approval->approvable->update(['status' => 'rejected', 'decision_reason' => $reason]);

            return $approval->approvable->fresh();
        });
    }

    public function receive(ReturnRequest $request, User $warehouseUser, ?string $notes = null): ReturnRequest
    {
        throw_unless($warehouseUser->can('return_requests.receive'), new AuthorizationException('You cannot receive approved returns.'));

        return DB::transaction(function () use ($request, $warehouseUser, $notes): ReturnRequest {
            $request = ReturnRequest::query()->with('items')->lockForUpdate()->findOrFail($request->id);
            throw_unless($warehouseUser->hasCompanyAccess((int) $request->company_id), new AuthorizationException('Cross-company return receipt is forbidden.'));
            throw_unless($request->status === 'approved', new \DomainException('Only an approved return request can be received.'));

            $return = $this->returns->create(
                (int) $request->company_id,
                (int) $request->user_id,
                (int) $request->customer_id,
                $request->items->map(fn ($item): array => [
                    'invoice_item_id' => (int) $item->invoice_item_id,
                    'quantity' => (float) $item->quantity,
                    'condition' => $item->condition,
                ])->all(),
                (int) $request->against_invoice_id,
                $request->visit_id,
                $request->reason,
            );

            $request->update([
                'status' => 'received',
                'return_record_id' => $return->id,
                'received_by' => $warehouseUser->id,
                'received_at' => now(),
                'receipt_notes' => $notes,
            ]);
            DB::afterCommit(fn () => app(WebhookService::class)->dispatch((int) $request->company_id, 'return.received', [
                'return_request_id' => $request->id,
                'return_record_id' => $return->id,
                'request_number' => $request->request_number,
                'customer_id' => $request->customer_id,
                'total' => $return->total,
            ]));

            return $request->fresh('returnRecord');
        });
    }
}
