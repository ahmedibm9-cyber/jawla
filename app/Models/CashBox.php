<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CashBoxFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property string $balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CashBox extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<CashBoxFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'user_id', 'balance'];

    protected $casts = ['balance' => 'decimal:2'];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
