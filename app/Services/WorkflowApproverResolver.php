<?php

namespace App\Services;

use App\Models\OrganizationUnit;
use App\Models\User;

class WorkflowApproverResolver
{
    /**
     * Resolve through the representative's supervisor/unit chain first.
     * Central functional reviews (for example finance reconciliation) may use
     * an explicit company fallback when no scoped holder exists.
     *
     * @param  list<int>  $excludedUserIds
     */
    public function forSubmitter(
        User $submitter,
        string $permission,
        array $excludedUserIds = [],
        bool $allowCompanyFallback = false,
    ): User {
        $submitter->loadMissing('representativeProfile.supervisor');
        $supervisor = $submitter->representativeProfile?->supervisor;
        if ($this->eligible($supervisor, $submitter, $permission, $excludedUserIds)) {
            return $supervisor;
        }

        foreach ($this->unitChain($submitter) as $unit) {
            if ($this->eligible($unit->manager, $submitter, $permission, $excludedUserIds)) {
                return $unit->manager;
            }
        }

        $hasOrganizationAssignment = $submitter->primary_organization_unit_id !== null
            || $submitter->organizationUnits()->exists();
        throw_if($hasOrganizationAssignment && ! $allowCompanyFallback, new \DomainException(
            'No eligible approver exists in the representative organization chain.',
        ));

        $approver = User::query()
            ->forCompany($submitter->activeCompanyId())
            ->where('is_active', true)
            ->whereNotIn('id', $excludedUserIds)
            ->permission($permission)
            ->orderBy('id')
            ->first();

        throw_if($approver === null, new \DomainException(
            "An active approver with {$permission} is required for this workflow.",
        ));

        return $approver;
    }

    /** @return list<OrganizationUnit> */
    private function unitChain(User $submitter): array
    {
        $unitIds = $submitter->organizationUnits()->pluck('organization_units.id')
            ->push($submitter->primary_organization_unit_id)->filter()->unique();
        $chain = collect();

        foreach ($unitIds as $unitId) {
            $unit = OrganizationUnit::query()->with('manager')->find($unitId);
            for ($depth = 0; $depth < 4 && $unit !== null; $depth++) {
                $chain->put($unit->id, $unit);
                $unit = $unit->parent()->with('manager')->first();
            }
        }

        return $chain->values()->all();
    }

    /** @param list<int> $excludedUserIds */
    private function eligible(?User $candidate, User $submitter, string $permission, array $excludedUserIds): bool
    {
        return $candidate !== null
            && $candidate->is_active
            && $candidate->hasCompanyAccess($submitter->activeCompanyId())
            && ! in_array((int) $candidate->id, $excludedUserIds, true)
            && $candidate->can($permission);
    }
}
