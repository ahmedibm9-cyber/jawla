<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name_ar
 * @property string $name_en
 * @property string|null $type
 * @property string|null $contact_person
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $payment_terms
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Supplier extends Model
{
    use Concerns\BelongsToCompany;

    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'company_id', 'code', 'name_ar', 'name_en', 'type',
        'contact_person', 'phone', 'email', 'address',
        'payment_terms', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
