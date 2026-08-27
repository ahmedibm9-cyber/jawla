<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int|null $tax_template_line_id
 * @property string $description
 * @property string $rate
 * @property string $amount
 * @property bool $included_in_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class InvoiceTax extends Model
{
    protected $fillable = [
        'invoice_id', 'tax_template_line_id',
        'description', 'rate', 'amount', 'included_in_rate',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'included_in_rate' => 'boolean',
    ];

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
