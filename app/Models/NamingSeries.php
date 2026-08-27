<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\NamingSeriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $prefix
 * @property string $series_format
 * @property int $current_number
 * @property int $pad_length
 * @property int $company_id
 * @property int $year
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class NamingSeries extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<NamingSeriesFactory> */
    use HasFactory;

    protected $table = 'naming_series';

    protected $fillable = [
        'name', 'prefix', 'series_format', 'current_number',
        'pad_length', 'company_id', 'year', 'is_active',
    ];

    protected $casts = [
        'current_number' => 'integer',
        'pad_length' => 'integer',
        'year' => 'integer',
        'is_active' => 'boolean',
    ];
}
