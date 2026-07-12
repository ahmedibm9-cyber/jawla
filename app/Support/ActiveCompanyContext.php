<?php

namespace App\Support;

final class ActiveCompanyContext
{
    private ?int $companyId = null;

    public function setFromUser($user): void
    {
        $this->companyId = $user?->company_id;
    }

    public function setCompanyId(?int $id): void
    {
        $this->companyId = $id;
    }

    public function id(): ?int
    {
        return $this->companyId;
    }

    public function disable(): void
    {
        $this->companyId = null;
    }
}
