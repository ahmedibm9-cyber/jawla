<?php

namespace App\Models;

use App\Enums\VanTransferStatus;
use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\VanTransferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $from_user_id
 * @property int $to_user_id
 * @property VanTransferStatus $status
 * @property Carbon|null $accepted_at
 * @property Carbon|null $shipped_at
 * @property Carbon|null $received_at
 * @property int|null $in_transit_warehouse_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VanTransfer extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    /** @use HasFactory<VanTransferFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'from_user_id', 'to_user_id', 'status',
        'accepted_at', 'shipped_at', 'received_at', 'in_transit_warehouse_id',
    ];

    protected $casts = [
        'status' => VanTransferStatus::class,
        'accepted_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /** @return HasMany<VanTransferItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(VanTransferItem::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function transitWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'in_transit_warehouse_id');
    }
}
