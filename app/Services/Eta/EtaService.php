<?php

namespace App\Services\Eta;

use App\Models\Activity;
use App\Models\Invoice;
use App\Services\Eta\Contracts\EtaClient;
use App\Services\Eta\Exceptions\EtaNotConfiguredException;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates ETA e-invoice submission. Hard-gated: refuses to run unless ETA
 * is enabled AND a taxpayer RIN is configured, so a demo/UAT deployment can
 * never accidentally submit to the tax authority. Once real credentials +
 * certificate + an HTTP EtaClient are provisioned, this becomes the go-live
 * submission path with no call-site changes.
 */
class EtaService
{
    public function __construct(
        private readonly EtaClient $client,
        private readonly EtaDocumentBuilder $builder,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('eta.enabled') && (string) config('eta.taxpayer_rin') !== '';
    }

    public function submit(Invoice $invoice): Invoice
    {
        throw_if(! $this->isEnabled(), EtaNotConfiguredException::make());

        return DB::transaction(function () use ($invoice): Invoice {
            $document = $this->builder->build($invoice);
            $result = $this->client->submit($document);

            $invoice->update([
                'eta_status' => $result->status,
                'eta_submission_uuid' => $result->uuid,
                'eta_long_id' => $result->longId,
                'eta_submitted_at' => now(),
                'eta_response' => $result->errors ? ['errors' => $result->errors] : $result->raw,
            ]);

            Activity::log(
                $result->accepted ? 'eta_invoice_submitted' : 'eta_invoice_rejected',
                $invoice,
                "ETA submission for invoice {$invoice->invoice_number}: {$result->status}",
            );

            return $invoice->fresh();
        });
    }
}
