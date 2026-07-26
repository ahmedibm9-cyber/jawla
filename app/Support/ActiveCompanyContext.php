<?php

namespace App\Support;

use App\Models\Company;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;

final class ActiveCompanyContext
{
    private ?int $companyId = null;

    private bool $allowUnscoped;

    public function __construct()
    {
        $this->allowUnscoped = app()->runningUnitTests();
    }

    public function setFromUser($user, ?int $companyId = null): void
    {
        if ($user === null) {
            $this->enforce();

            return;
        }

        $resolved = $companyId ?? (int) $user->company_id;

        if ($resolved < 1 || ! $user->hasCompanyAccess($resolved)) {
            throw new AuthorizationException(
                'The selected company is not assigned to this user.'
            );
        }

        $this->setCompanyId($resolved);
    }

    public function setCompanyId(int $id): void
    {
        if ($id < 1) {
            throw new \InvalidArgumentException('Company id must be a positive integer.');
        }

        $this->companyId = $id;
        $this->allowUnscoped = false;
    }

    public function id(): ?int
    {
        return $this->companyId;
    }

    public function company(): ?Company
    {
        return $this->companyId === null ? null : Company::find($this->companyId);
    }

    public function enforce(): void
    {
        $this->companyId = null;
        $this->allowUnscoped = false;
    }

    public function allowsUnscoped(): bool
    {
        return $this->allowUnscoped;
    }

    public function assertMatches(int $companyId): void
    {
        if ($this->companyId === $companyId) {
            return;
        }

        if ($this->companyId === null && $this->allowUnscoped) {
            return;
        }

        throw new AuthorizationException(
            'The requested operation does not belong to the active company.'
        );
    }

    public function runWithCompany(int $companyId, Closure $callback): mixed
    {
        $previousId = $this->companyId;
        $previousAllowance = $this->allowUnscoped;
        $this->setCompanyId($companyId);

        try {
            return $callback();
        } finally {
            $this->companyId = $previousId;
            $this->allowUnscoped = $previousAllowance;
        }
    }

    public function disable(): void
    {
        $this->companyId = null;
        $this->allowUnscoped = app()->runningUnitTests();
    }
}
