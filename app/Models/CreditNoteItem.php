<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Model;

class CreditNoteItem extends Model
{
    use AppendOnly;

    protected $fillable = [
        'credit_note_id', 'invoice_item_id', 'return_item_id', 'product_id', 'batch_id',
        'quantity', 'unit_price', 'line_total', 'tax_amount', 'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];
}
