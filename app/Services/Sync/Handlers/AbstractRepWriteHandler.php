<?php

namespace App\Services\Sync\Handlers;

use App\Models\Customer;
use App\Models\User;
use App\Services\Sync\Contracts\SyncHandler;
use Illuminate\Support\Facades\Validator;

/**
 * Shared base for offline rep-write handlers. Provides server-side payload
 * validation and a company-ownership guard so a tampered offline payload can
 * never write into or reference another tenant's data — company and user always
 * come from the authenticated rep, never the payload.
 */
abstract class AbstractRepWriteHandler implements SyncHandler
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function validated(array $payload, array $rules): array
    {
        return Validator::make($payload, $rules)->validate();
    }

    /** Reject a customer id that does not belong to the rep's company. */
    protected function assertCustomerInCompany(User $rep, int $customerId): void
    {
        $ok = Customer::query()
            ->where('company_id', $rep->company_id)
            ->whereKey($customerId)
            ->exists();

        throw_unless($ok, new \RuntimeException('Customer does not belong to this company.'));
    }
}
