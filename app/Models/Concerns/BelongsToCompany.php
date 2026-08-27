<?php

namespace App\Models\Concerns;

use App\Support\ActiveCompanyContext;
use Illuminate\Auth\Access\AuthorizationException;
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
            } elseif (! app(ActiveCompanyContext::class)->allowsUnscoped()) {
                $builder->whereRaw('1 = 0');
            }
        });

        static::creating(function (Model $model): void {
            $context = app(ActiveCompanyContext::class);
            $companyId = $context->id();

            if ($companyId === null) {
                if (! $context->allowsUnscoped()) {
                    throw new AuthorizationException('An active company is required for this write.');
                }

                return;
            }

            if ($model->getAttribute('company_id') === null) {
                $model->setAttribute('company_id', $companyId);
            } elseif ((int) $model->getAttribute('company_id') !== $companyId) {
                throw new AuthorizationException('Cross-company writes are forbidden.');
            }
        });

        $assertOwnedByActiveCompany = function (Model $model): void {
            $context = app(ActiveCompanyContext::class);
            $companyId = $context->id();

            if ($companyId === null) {
                if (! $context->allowsUnscoped()) {
                    throw new AuthorizationException('An active company is required for this write.');
                }

                return;
            }

            if ((int) $model->getAttribute('company_id') !== $companyId) {
                throw new AuthorizationException('Cross-company writes are forbidden.');
            }
        };

        static::updating($assertOwnedByActiveCompany);
        static::deleting($assertOwnedByActiveCompany);
    }
}
