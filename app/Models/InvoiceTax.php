<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
