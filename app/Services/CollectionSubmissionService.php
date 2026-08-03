<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\CollectionSubmission;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CollectionSubmissionService
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly WorkflowApproverResolver $approverResolver,
        private readonly PaymentService $payments,
    ) {}

    public function submit(User $rep, int $customerId, float $amount, string $method, array $attributes = []): CollectionSubmission
    {
        return DB::transaction(function () use ($rep, $customerId, $amount, $method, $attributes): CollectionSubmission {
            throw_unless($rep->can('payments.collect'), new AuthorizationException('You cannot capture collections.'));
            throw_unless(in_array($method, ['cash', 'cheque', 'transfer', 'other'], true), new \DomainException('Invalid collection method.'));
            throw_if($amount <= 0, new \DomainException('Collection amount must be greater than zero.'));
            $companyId = $rep->activeCompanyId();
            $customer = Customer::query()->findOrFail($customerId);
            $invoiceId = $attributes['invoice_id'] ?? null;
            if ($invoiceId !== null) {
                Invoice::query()->whereKey($invoiceId)->where('customer_id', $customer->id)->firstOrFail();
            }
            $reference = trim((string) ($attributes['reference_number'] ?? ''));
            throw_if(in_array($method, ['cheque', 'transfer'], true) && $reference === '', new \DomainException(
                'A cheque or transfer reference number is required.',
            ));

            $submission = CollectionSubmission::create([
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'user_id' => $rep->id,
                'invoice_id' => $invoiceId,
                'visit_id' => $attributes['visit_id'] ?? null,
                'amount' => number_format($amount, 2, '.', ''),
                'method' => $method,
                'reference_number' => $reference ?: null,
                'notes' => $attributes['notes'] ?? null,
                'status' => 'pending_review',
                'captured_at' => now(),
            ]);
            $this->approvals->submit($submission, $rep, [$this->approverResolver->forCompany($companyId)]);

            return $submission->fresh(['approvals.steps']);
        });
    }

    public function approve(ApprovalRequest $request, User $actor): CollectionSubmission
    {
        throw_unless($actor->can('collections.review'), new AuthorizationException('You cannot review collections.'));

        return DB::transaction(function () use ($request, $actor): CollectionSubmission {
            $request = $this->approvals->approve($request, $actor);
            throw_unless($request->approvable instanceof CollectionSubmission, new \InvalidArgumentException('Not a collection approval.'));
            $submission = $request->approvable;
            $payment = $this->payments->collect(
                (int) $submission->company_id,
                (int) $submission->user_id,
                (int) $submission->customer_id,
                (float) $submission->amount,
                $submission->method,
                $submission->invoice_id,
                $submission->visit_id,
                $submission->notes,
                'collection-'.$submission->id,
            );
            $submission->update(['status' => 'approved', 'payment_id' => $payment->id, 'reviewed_by' => $actor->id, 'reviewed_at' => now()]);
            DB::afterCommit(fn () => app(WebhookService::class)->dispatch((int) $submission->company_id, 'collection.approved', [
                'collection_submission_id' => $submission->id,
                'payment_id' => $payment->id,
                'customer_id' => $submission->customer_id,
                'amount' => $submission->amount,
                'method' => $submission->method,
            ]));

            return $submission->fresh('payment');
        });
    }

    public function reject(ApprovalRequest $request, User $actor, string $reason): CollectionSubmission
    {
        throw_unless($actor->can('collections.review'), new AuthorizationException('You cannot review collections.'));
        $reason = trim($reason);
        throw_if($reason === '', new \DomainException('A rejection reason is required.'));

        return DB::transaction(function () use ($request, $actor, $reason): CollectionSubmission {
            $request = $this->approvals->reject($request, $actor, $reason);
            throw_unless($request->approvable instanceof CollectionSubmission, new \InvalidArgumentException('Not a collection approval.'));
            $request->approvable->update(['status' => 'rejected', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_reason' => $reason]);

            return $request->approvable->fresh();
        });
    }
}
