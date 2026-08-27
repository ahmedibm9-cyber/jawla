<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int|null $supervisor_id
 * @property string|null $job_title
 * @property Carbon|null $hire_date
 * @property string|null $vehicle_code
 * @property string|null $national_id
 * @property string|null $emergency_contact
 * @property array<string, mixed>|null $skills
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RepresentativeProfile extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'supervisor_id', 'job_title', 'hire_date',
        'vehicle_code', 'national_id', 'emergency_contact', 'skills',
    ];

    protected function casts(): array
    {
        return ['hire_date' => 'date', 'skills' => 'array'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
