<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ModeOfPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $type
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ModeOfPayment extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<ModeOfPaymentFactory> */
    use HasFactory;

    protected $table = 'modes_of_payment';

    protected $fillable = ['company_id', 'name', 'type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
