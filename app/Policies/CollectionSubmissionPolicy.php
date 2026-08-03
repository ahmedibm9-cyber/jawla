<?php

namespace App\Policies;

use App\Models\CollectionSubmission;
use App\Models\User;
use App\Policies\Concerns\ChecksCompanyOwnership;

class CollectionSubmissionPolicy
{
    use ChecksCompanyOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any:collection_submission');
    }

    public function view(User $user, CollectionSubmission $record): bool
    {
        return $user->can('view:collection_submission') && $this->matchesCompany($user, $record);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CollectionSubmission $record): bool
    {
        return $user->can('collections.review') && $this->matchesCompany($user, $record);
    }

    public function delete(User $user, CollectionSubmission $record): bool
    {
        return false;
    }
}
