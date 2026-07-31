<?php

namespace App\Services;

use App\Models\CashBox;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class RepProvisioningService
{
    public function provision(User $user): void
    {
        $companyId = $user->company_id;
        if ($companyId === null) {
            return;
        }

        DB::transaction(function () use ($user, $companyId): void {
            Warehouse::firstOrCreate(
                ['company_id' => $companyId, 'user_id' => $user->id, 'type' => 'van'],
                ['name_ar' => "مخزن {$user->name}", 'name_en' => "{$user->name} Van", 'is_active' => true],
            );

            CashBox::withoutGlobalScopes()
                ->firstOrCreate(
                    ['user_id' => $user->id],
                    ['company_id' => $companyId, 'balance' => 0],
                );
        });
    }
}
