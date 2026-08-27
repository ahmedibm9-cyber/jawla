<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\BelongsToCompany;
use App\Support\ActiveCompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property string $type
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $description
 * @property array<string, mixed>|null $properties
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property bool $is_reversed
 * @property int|null $reversed_by
 * @property Carbon|null $reversed_at
 * @property int|null $reversal_of
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Activity extends Model
{
    use AppendOnly;
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'type',
        'subject_type', 'subject_id',
        'description', 'properties',
        'ip_address', 'user_agent',
        'is_reversed', 'reversed_by', 'reversed_at', 'reversal_of',
    ];

    protected $casts = [
        'properties' => 'array',
        'is_reversed' => 'boolean',
        'reversed_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @param array<string, mixed> $properties */
    public static function log(string $type, ?Model $subject = null, ?string $description = null, array $properties = []): self
    {
        $user = auth()->user();

        return self::create([
            'company_id' => app(ActiveCompanyContext::class)->id()
                ?? ($user?->getAttribute('company_id'))
                ?? ($subject?->getAttribute('company_id')),
            'user_id' => $user?->id,
            'type' => $type,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
