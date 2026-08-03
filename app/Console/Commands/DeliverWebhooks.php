<?php

namespace App\Console\Commands;

use App\Services\WebhookService;
use Illuminate\Console\Command;

class DeliverWebhooks extends Command
{
    protected $signature = 'app:deliver-webhooks {--limit=50}';

    protected $description = 'Deliver due webhook outbox entries with leases and bounded retries';

    public function handle(WebhookService $webhooks): int
    {
        $count = $webhooks->deliverDue(max(1, min(500, (int) $this->option('limit'))));
        $this->info("Processed {$count} webhook deliveries.");

        return self::SUCCESS;
    }
}
