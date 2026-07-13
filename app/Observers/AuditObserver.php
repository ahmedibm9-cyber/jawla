<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\PriceQuotation;
use App\Models\User;

class AuditObserver
{
    public function updated($model): void
    {
        if (! $model instanceof User) {
            return;
        }

        $dirty = $model->getChanges();
        unset($dirty['updated_at']);

        if (empty($dirty)) {
            return;
        }

        Activity::log('user_edit', $model, "User edited: {$model->email}", ['changed' => $dirty]);
    }

    public function created($model): void
    {
        if (! $model instanceof PriceQuotation) {
            return;
        }

        Activity::log('price_change', $model, "Price set: base {$model->base_price}", [
            'base_price' => (string) $model->base_price,
            'manager_plus' => (string) $model->manager_plus,
            'manager_minus' => (string) $model->manager_minus,
        ]);
    }
}