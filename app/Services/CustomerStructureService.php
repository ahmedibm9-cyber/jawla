<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\CustomerContact;
use App\Models\CustomerLocation;
use App\Models\CustomerOutlet;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CustomerStructureService
{
    /** @param array<string, mixed> $attributes */
    public function createOutlet(Customer $customer, array $attributes): CustomerOutlet
    {
        return DB::transaction(fn (): CustomerOutlet => $customer->outlets()->create(Arr::only($attributes, [
            'route_id', 'code', 'name_ar', 'name_en', 'phone', 'is_active',
        ])));
    }

    /** @param array<string, mixed> $attributes */
    public function addContact(Customer $customer, array $attributes): CustomerContact
    {
        return DB::transaction(function () use ($customer, $attributes): CustomerContact {
            $outlet = $this->outletForCustomer($customer, $attributes['customer_outlet_id'] ?? null);

            if ($attributes['is_primary'] ?? false) {
                $customer->contacts()->where('customer_outlet_id', $outlet?->id)->update(['is_primary' => false]);
            }

            return $customer->contacts()->create([
                ...Arr::only($attributes, ['name', 'job_title', 'phone', 'email', 'is_primary']),
                'customer_outlet_id' => $outlet?->id,
            ]);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function addLocation(Customer $customer, array $attributes): CustomerLocation
    {
        return DB::transaction(function () use ($customer, $attributes): CustomerLocation {
            $outlet = $this->outletForCustomer($customer, $attributes['customer_outlet_id'] ?? null);
            $type = (string) ($attributes['type'] ?? 'visit');

            if ($attributes['is_primary'] ?? false) {
                $customer->locations()
                    ->where('customer_outlet_id', $outlet?->id)
                    ->where('type', $type)
                    ->update(['is_primary' => false]);
            }

            return $customer->locations()->create([
                ...Arr::only($attributes, [
                    'label', 'address', 'latitude', 'longitude', 'geofence_radius_m',
                    'is_primary', 'is_active',
                ]),
                'customer_outlet_id' => $outlet?->id,
                'type' => $type,
            ]);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function assignRep(Customer $customer, User $rep, User $actor, array $attributes = []): CustomerAssignment
    {
        return DB::transaction(function () use ($customer, $rep, $actor, $attributes): CustomerAssignment {
            throw_unless($actor->can('visit_assignments.manage'), new AuthorizationException(
                'You do not have permission to assign customers.',
            ));
            throw_unless(
                $rep->hasCompanyAccess((int) $customer->company_id) && $actor->hasCompanyAccess((int) $customer->company_id),
                new AuthorizationException('Cross-company customer assignments are forbidden.'),
            );

            $outlet = $this->outletForCustomer($customer, $attributes['customer_outlet_id'] ?? null);

            return CustomerAssignment::query()->updateOrCreate([
                'customer_id' => $customer->id,
                'customer_outlet_id' => $outlet?->id,
                'user_id' => $rep->id,
            ], [
                'company_id' => $customer->company_id,
                'assigned_by' => $actor->id,
                'assignment_type' => $attributes['assignment_type'] ?? 'primary',
                'starts_on' => $attributes['starts_on'] ?? null,
                'ends_on' => $attributes['ends_on'] ?? null,
                'is_active' => $attributes['is_active'] ?? true,
            ]);
        });
    }

    private function outletForCustomer(Customer $customer, mixed $outletId): ?CustomerOutlet
    {
        if (blank($outletId)) {
            return null;
        }

        return $customer->outlets()->findOrFail((int) $outletId);
    }
}
