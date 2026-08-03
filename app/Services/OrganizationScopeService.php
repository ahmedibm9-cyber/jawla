<?php

namespace App\Services;

use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OrganizationScopeService
{
    public function canAccessUser(User $actor, int $userId): bool
    {
        return $this->scopeUsers(User::query()->whereKey($userId), $actor)->exists();
    }

    /**
     * Limit a user query to the actor's assigned organization subtree.
     * Administrators and users without an explicit unit retain company-wide
     * access, preserving existing installations until scopes are configured.
     */
    public function scopeUsers(Builder $query, User $actor): Builder
    {
        if ($actor->can('organization_units.view_all')) {
            return $query;
        }

        $rootIds = $actor->organizationUnits()->pluck('organization_units.id')
            ->push($actor->primary_organization_unit_id)
            ->filter()
            ->unique()
            ->values();

        if ($rootIds->isEmpty()) {
            return $query;
        }

        $unitIds = $this->descendantIds($rootIds->all());

        return $query->where(function (Builder $query) use ($unitIds): void {
            $query->whereIn('primary_organization_unit_id', $unitIds)
                ->orWhereHas('organizationUnits', fn (Builder $units) => $units->whereKey($unitIds));
        });
    }

    /** @param list<int> $rootIds @return list<int> */
    public function descendantIds(array $rootIds): array
    {
        $all = collect($rootIds)->map(fn ($id): int => (int) $id)->unique();
        $frontier = $all;

        // The supported hierarchy is region → branch → area → team.
        for ($depth = 0; $depth < 3 && $frontier->isNotEmpty(); $depth++) {
            $children = OrganizationUnit::query()
                ->whereIn('parent_id', $frontier->all())
                ->whereNotIn('id', $all->all())
                ->pluck('id')
                ->values();
            $all = $all->merge($children)->unique();
            $frontier = $children;
        }

        return $all->values()->all();
    }
}
