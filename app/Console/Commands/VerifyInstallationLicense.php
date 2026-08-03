<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class VerifyInstallationLicense extends Command
{
    protected $signature = 'app:verify-license';

    protected $description = 'Verify the installed vendor-signed Jawla license and licensed user limit';

    public function handle(LicenseService $licenses): int
    {
        try {
            $license = $licenses->assertValid();
            $this->info("License {$license->license_id} is active until {$license->expires_at->format('Y-m-d')}.");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
