<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tax_template_id
 * @property string|null $description
 * @property string $charge_type
 * @property string $rate
 * @property bool $included_in_rate
 * @property string|null $row_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TaxTemplateLine extends Model
{
    protected $fillable = [
        'tax_template_id', 'description', 'charge_type',
        'rate', 'included_in_rate', 'row_id',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'included_in_rate' => 'boolean',
    ];

    /** @return BelongsTo<TaxTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TaxTemplate::class, 'tax_template_id');
    }
}
