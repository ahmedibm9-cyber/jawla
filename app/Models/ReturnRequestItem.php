<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $return_request_id
 * @property int $invoice_item_id
 * @property string $quantity
 * @property string $condition
 * @property string $unit_price
 * @property string $line_total
 * @property string $tax_amount
 * @property string $total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ReturnRequestItem extends Model
{
    protected $fillable = ['return_request_id', 'invoice_item_id', 'quantity', 'condition', 'unit_price', 'line_total', 'tax_amount', 'total'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ReturnRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id');
    }

    /** @return BelongsTo<InvoiceItem, $this> */
    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }
}
