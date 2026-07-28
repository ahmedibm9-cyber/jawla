<?php

namespace App\Services;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LocationPurgeService
{
    public function purge(int $retentionDays = 90): int
    {
        if ($retentionDays < 1) {
            $retentionDays = 90;
        }

        $cutoff = Carbon::now()->subDays($retentionDays);

        $total = 0;
        foreach (Company::pluck('id') as $companyId) {
            // ponytail: chunk delete per company to avoid long locks
            do {
                $deleted = DB::table('location_pings')
                    ->where('company_id', $companyId)
                    ->where('recorded_at', '<', $cutoff)
                    ->limit(1000)
                    ->delete();
                $total += $deleted;
            } while ($deleted === 1000);
        }

        return $total;
    }
}
