<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function template(): BelongsTo { return $this->belongsTo(TaxTemplate::class, 'tax_template_id'); }
}