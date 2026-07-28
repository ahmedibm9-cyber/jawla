<?php

namespace App\Console\Commands;

use App\Services\LocationPurgeService;
use Illuminate\Console\Command;

class PurgeLocationPings extends Command
{
    protected $signature = 'app:purge-location-pings {--days= : Override retention period}';

    protected $description = 'Delete location pings older than the configured retention period';

    public function handle(LocationPurgeService $purge): int
    {
        $days = $this->option('days')
            ?? (int) config('jawla.retention.location_pings_days', 90);

        $deleted = $purge->purge($days);

        $this->info("Purged {$deleted} location pings older than {$days} days.");

        return self::SUCCESS;
    }
}
