<?php

namespace App\Models\Concerns;

use App\Support\ActiveCompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $companyId = app(ActiveCompanyContext::class)->id();

            if ($companyId !== null) {
                $builder->where($builder->getModel()->getTable().'.company_id', $companyId);
            }
        });

        static::creating(function (Model $model): void {
            if ($model->company_id === null) {
                $model->company_id = app(ActiveCompanyContext::class)->id();
            }
        });
    }
}
