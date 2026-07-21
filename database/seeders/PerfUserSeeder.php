<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a pool of rep accounts (perf-rep-1..N@jawla.test) for load testing.
 * Auth is rate-limited 5/min per IP+email, so k6 auth stress needs many accounts
 * to spread load across — one account throttles after 5 hits and the numbers
 * become noise. Pair with tests/stress/k6-login-stress.js (PERF_POOL_SIZE=N).
 *
 * Idempotent. Run against STAGING only — never seed perf logins into production.
 * Count via PERF_POOL_SIZE (default 20), password via PERF_POOL_PASSWORD.
 */
class PerfUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->orderBy('id')->first();
        if ($company === null) {
            $this->command?->warn('PerfUserSeeder: no company found — run DemoSeeder first.');

            return;
        }

        $count = (int) (env('PERF_POOL_SIZE') ?: 20);
        $password = Hash::make((string) (env('PERF_POOL_PASSWORD') ?: 'password'));

        for ($i = 1; $i <= $count; $i++) {
            $user = User::firstOrCreate(
                ['email' => "perf-rep-{$i}@jawla.test"],
                [
                    'company_id' => $company->id,
                    'name' => "Perf Rep {$i}",
                    'employee_code' => "PERF-{$i}",
                    'password' => $password,
                    'is_active' => true,
                ],
            );

            if (! $user->hasRole('rep')) {
                $user->assignRole('rep');
            }
        }

        $this->command?->info("PerfUserSeeder: {$count} perf rep accounts ready (perf-rep-1..{$count}@jawla.test).");
    }
}
