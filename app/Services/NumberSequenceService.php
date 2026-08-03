<?php

namespace App\Services;

use App\Models\Company;
use App\Models\NamingSeries;
use App\Services\Contracts\DocumentNumberService;
use App\Support\ActiveCompanyContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * PR-027: gapless, per-(company, doc_type, calendar year) sequential numbering
 * for legally-named documents (sales invoices, sales returns, payments,
 * customer credit notes, refunds).
 *
 * Properties:
 *  - **Gapless.** The whole service (find-or-create + lock + increment)
 *    runs inside one transaction. If the caller wraps the call in their
 *    own transaction and rolls it back, the entire service operation is
 *    reverted. The next successful call starts from the same counter, so
 *    a failed attempt never burns a number.
 *  - **Concurrent.** Allocation is serialized by `SELECT ... FOR UPDATE`
 *    on the per-(company, doc_type, year) row, so two simultaneous calls
 *    in separate connections each get a distinct, monotonically-increasing
 *    number.
 *  - **Per-year reset.** The series resets to 1 at the start of every
 *    calendar year. Prior years' series rows are retained permanently for
 *    audit; generating against a prior year re-uses that row's counter.
 *  - **Monotonic and predictable.** The formatted number is
 *    `PREFIX-ABBR-YYYY-NNNNN` (no random suffix). The client can
 *    visually verify sequence from the displayed number alone.
 */
class NumberSequenceService implements DocumentNumberService
{
    /**
     * Explicit prefix mapping for known legal doc types. Anything not in this
     * map falls back to the alpha-only uppercase of the type, which is
     * preserved for back-compat with custom doc types.
     *
     * @var array<string, string>
     */
    private const PREFIXES = [
        'sales_invoice' => 'INV',
        'sales_return' => 'RET',
        'payment' => 'PAY',
        'credit_note' => 'CN',
        'refund' => 'REF',
        'proforma' => 'PI',
        'purchase_request' => 'PR',
        'purchase_order' => 'PO',
        'sales_order' => 'SO',
        'return_request' => 'RR',
    ];

    public function generate(string $docType, int $companyId, ?int $year = null): string
    {
        app(ActiveCompanyContext::class)->assertMatches($companyId);

        $year ??= (int) date('Y');

        return DB::transaction(function () use ($docType, $companyId, $year): string {
            $series = NamingSeries::where('name', $docType)
                ->where('company_id', $companyId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $series) {
                try {
                    $series = NamingSeries::create([
                        'name' => $docType,
                        'prefix' => $this->prefixFor($docType),
                        'series_format' => '{PREFIX}-{ABBR}-{YYYY}-{#####}',
                        'current_number' => 0,
                        'pad_length' => 5,
                        'company_id' => $companyId,
                        'year' => $year,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Lost the create race; the winning transaction has
                    // already inserted the row. Re-fetch with the lock.
                    $series = NamingSeries::where('name', $docType)
                        ->where('company_id', $companyId)
                        ->where('year', $year)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $next = $series->current_number + 1;
            $series->current_number = $next;
            $series->save();

            $abbr = Company::where('id', $companyId)->value('abbr') ?? 'XX';

            return $this->formatNumber($series->prefix, $abbr, $year, $next, $series->pad_length);
        });
    }

    private function formatNumber(string $prefix, string $abbr, int $year, int $counter, int $padLength): string
    {
        return $prefix.'-'.$abbr.'-'.
            str_pad((string) $year, 4, '0', STR_PAD_LEFT).'-'.
            str_pad((string) $counter, $padLength, '0', STR_PAD_LEFT);
    }

    private function prefixFor(string $docType): string
    {
        if (isset(self::PREFIXES[$docType])) {
            return self::PREFIXES[$docType];
        }

        return strtoupper(preg_replace('/[^a-z]/i', '', $docType));
    }
}
