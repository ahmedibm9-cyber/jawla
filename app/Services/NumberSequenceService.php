<?php

namespace App\Services;

use App\Models\Company;
use App\Models\NamingSeries;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class NumberSequenceService implements Contracts\DocumentNumberService
{
    public function generate(string $docType, int $companyId): string
    {
        return DB::transaction(function () use ($docType, $companyId): string {
            $series = NamingSeries::where('name', $docType)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            if (! $series) {
                try {
                    $series = NamingSeries::create([
                        'name' => $docType,
                        'prefix' => strtoupper(preg_replace('/[^a-z]/i', '', $docType)),
                        'series_format' => '{YYYY}-{#####}',
                        'current_number' => 0,
                        'pad_length' => 5,
                        'company_id' => $companyId,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    $series = NamingSeries::where('name', $docType)
                        ->where('company_id', $companyId)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $next = $series->current_number + 1;
            $series->update(['current_number' => $next]);

            $abbr = Company::where('id', $companyId)->value('abbr') ?? 'XX';

            return $series->prefix.'-'.$abbr.'-'.date('Y').'-'.
                str_pad((string) $next, $series->pad_length, '0', STR_PAD_LEFT);
        });
    }
}
