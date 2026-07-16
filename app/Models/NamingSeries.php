<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NamingSeries extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'naming_series';

    protected $fillable = [
        'name', 'prefix', 'series_format', 'current_number',
        'pad_length', 'company_id', 'is_active',
    ];

    protected $casts = [
        'current_number' => 'integer',
        'pad_length' => 'integer',
        'is_active' => 'boolean',
    ];
}
