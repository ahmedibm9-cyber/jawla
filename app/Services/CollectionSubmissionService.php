<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\CollectionSubmission;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CollectionSubmissionService
{
    public function __construct(
        private readonly ApprovalService $approvals,
        private readonly WorkflowApproverResolver $approverResolver,
        private readonly PaymentService $payments,
        private readonly LicenseService $licenses,
        private readonly WebhookService $webhooks,
        private readonly OrganizationScopeService $organizationScope,
    ) {}

    public function submit(User $rep, int $customerId, float $amount, string $method, array $attributes = []): CollectionSubmission
    {
        $this->licenses->assertRuntimeFeature('field_sales');

        $companyId = $rep->activeCompanyId();
        $invoiceId = $attributes['invoice_id'] ?? null;
        $existing = CollectionSubmission::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('user_id', $rep->id)
            ->where('invoice_id', $invoiceId)
            ->where('status', 'pending_review')
            ->exists();
        if ($existing) {
            throw new \DomainException('A pending submission already exists for this customer/invoice.');
        }

        return DB::transaction(function () use ($rep, $customerId, $amount, $method, $attributes): CollectionSubmission {
            throw_unless($rep->can('payments.collect'), new AuthorizationException('You cannot capture collections.'));
            throw_unless(in_array($method, ['cash', 'cheque', 'transfer', 'other'], true), new \DomainException('Invalid collection method.'));
            throw_if($amount <= 0, new \DomainException('Collection amount must be greater than zero.'));
            $companyId = $rep->activeCompanyId();
            $customer = Customer::query()->whereKey($customerId)->where('company_id', $companyId)->firstOrFail();
            $invoiceId = $attributes['invoice_id'] ?? null;
            if ($invoiceId !== null) {
                Invoice::query()->whereKey($invoiceId)->where('company_id', $companyId)
                    ->where('customer_id', $customer->id)->firstOrFail();
            }
            $reference = trim((string) ($attributes['reference_number'] ?? ''));
            throw_if(in_array($method, ['cheque', 'transfer'], true) && $reference === '', new \DomainException(
                'A cheque or transfer reference number is required.',
            ));

            $evidenceIds = array_values(array_unique(array_map('intval', $attributes['evidence_photo_ids'] ?? [])));
            throw_if($evidenceIds === [], new \DomainException('Collection evidence is required.'));

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
            $photos = Photo::withoutGlobalScopes()->where('company_id', $companyId)
                ->where('user_id', $rep->id)->whereNull('photable_type')->whereNull('photable_id')
                ->whereIn('id', $evidenceIds)->lockForUpdate()->get();
            throw_unless($photos->count() === count($evidenceIds), new \DomainException(
                'Every evidence photo must belong to the submitting representative and be unattached.',
            ));
            foreach ($photos as $photo) {
                $photo->update(['photable_type' => $submission->getMorphClass(), 'photable_id' => $submission->id]);
            }

            $supervisor = $this->approverResolver->forSubmitter($rep, 'collections.review');
            $finance = $this->approverResolver->forSubmitter(
                $rep,
                'collections.reconcile',
                [(int) $supervisor->id],
                allowCompanyFallback: true,
            );
            $this->approvals->submit($submission, $rep, [$supervisor, $finance]);

            return $submission->fresh(['approvals.steps']);
        });
    }

    public function approve(ApprovalRequest $request, User $actor): CollectionSubmission
    {
        $this->licenses->assertRuntimeFeature('field_sales');

        return DB::transaction(function () use ($request, $actor): CollectionSubmission {
            $request = ApprovalRequest::query()->lockForUpdate()->findOrFail($request->id);
            $request->load('approvable');
            throw_unless($request->approvable instanceof CollectionSubmission, new \InvalidArgumentException('Not a collection approval.'));
            throw_unless($this->organizationScope->canAccessUser($actor, (int) $request->approvable->user_id), new AuthorizationException(
                'This collection belongs to another organization scope.',
            ));
            $sequence = (int) $request->current_sequence;
            $permission = $sequence === 1 ? 'collections.review' : 'collections.reconcile';
            throw_unless($actor->can($permission), new AuthorizationException('You cannot decide this collection stage.'));
            $request = $this->approvals->approve($request, $actor);
            throw_unless($request->approvable instanceof CollectionSubmission, new \InvalidArgumentException('Not a collection approval.'));
            $submission = $request->approvable;
            throw_unless($submission->photos()->exists(), new \DomainException('Collection evidence is required before review.'));

            if ($sequence === 1) {
                $submission->update([
                    'status' => 'supervisor_reviewed',
                    'supervisor_reviewed_by' => $actor->id,
                    'supervisor_reviewed_at' => now(),
                ]);
            } else {
                $submission->update([
                    'status' => 'finance_reviewed',
                    'finance_reviewed_by' => $actor->id,
                    'finance_reviewed_at' => now(),
                ]);
            }

            return $submission->fresh();
        });
    }

    public function reconcile(CollectionSubmission $submission, User $actor): CollectionSubmission
    {
        $this->licenses->assertRuntimeFeature('field_sales');
        throw_unless($actor->can('collections.reconcile'), new AuthorizationException('You cannot reconcile collections.'));

        return DB::transaction(function () use ($submission, $actor): CollectionSubmission {
            $submission = CollectionSubmission::query()->lockForUpdate()->findOrFail($submission->id);
            throw_unless($this->organizationScope->canAccessUser($actor, (int) $submission->user_id), new AuthorizationException(
                'This collection belongs to another organization scope.',
            ));
            throw_unless($submission->status === 'finance_reviewed', new \DomainException('Only a finance-reviewed collection can be reconciled.'));
            throw_unless((int) $submission->finance_reviewed_by === (int) $actor->id, new AuthorizationException(
                'Only the finance reviewer may reconcile this collection.',
            ));
            throw_unless($submission->photos()->exists(), new \DomainException('Collection evidence is required before reconciliation.'));

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
            $submission->update([
                'status' => 'reconciled',
                'payment_id' => $payment->id,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'reconciled_by' => $actor->id,
                'reconciled_at' => now(),
            ]);
            $this->webhooks->dispatch((int) $submission->company_id, 'collection.approved', [
                'collection_submission_id' => $submission->id,
                'payment_id' => $payment->id,
                'customer_id' => $submission->customer_id,
                'amount' => $submission->amount,
                'method' => $submission->method,
            ]);

            return $submission->fresh('payment');
        });
    }

    public function reject(ApprovalRequest $request, User $actor, string $reason): CollectionSubmission
    {
        $this->licenses->assertRuntimeFeature('field_sales');
        $reason = trim($reason);
        throw_if($reason === '', new \DomainException('A rejection reason is required.'));

        return DB::transaction(function () use ($request, $actor, $reason): CollectionSubmission {
            $request = ApprovalRequest::query()->lockForUpdate()->findOrFail($request->id);
            $request->load('approvable');
            throw_unless($request->approvable instanceof CollectionSubmission, new \InvalidArgumentException('Not a collection approval.'));
            throw_unless($this->organizationScope->canAccessUser($actor, (int) $request->approvable->user_id), new AuthorizationException(
                'This collection belongs to another organization scope.',
            ));
            $permission = (int) $request->current_sequence === 1 ? 'collections.review' : 'collections.reconcile';
            throw_unless($actor->can($permission), new AuthorizationException('You cannot reject this collection stage.'));
            $request = $this->approvals->reject($request, $actor, $reason);
            throw_unless($request->approvable instanceof CollectionSubmission, new \InvalidArgumentException('Not a collection approval.'));
            $request->approvable->update(['status' => 'rejected', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_reason' => $reason]);

            return $request->approvable->fresh();
        });
    }
}
