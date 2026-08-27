<?php

namespace App\Models;

use App\Enums\VisitPurpose;
use App\Enums\VisitStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int $customer_id
 * @property int $route_id
 * @property int|null $work_session_id
 * @property int|null $daily_visit_assignment_id
 * @property VisitPurpose $purpose
 * @property VisitStatus $status
 * @property bool $is_out_of_route
 * @property bool $arrival_confirmed
 * @property Carbon|null $arrival_confirmed_at
 * @property bool|null $arrival_flag
 * @property float|null $checkin_distance_m
 * @property float|null $checkin_accuracy_m
 * @property string|null $checkin_latitude
 * @property string|null $checkin_longitude
 * @property Carbon|null $checkin_at
 * @property string|null $checkout_latitude
 * @property string|null $checkout_longitude
 * @property Carbon|null $checkout_at
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Customer $customer
 * @property-read Route $route
 * @property-read WorkSession|null $workSession
 * @property-read DailyVisitAssignment|null $dailyVisitAssignment
 * @property-read Collection<int, Invoice> $invoices
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, ReturnRecord> $returns
 * @property-read VisitReport|null $report
 */
class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id', 'user_id', 'customer_id', 'route_id', 'work_session_id',
        'daily_visit_assignment_id',
        'purpose', 'status', 'is_out_of_route',
        'arrival_confirmed', 'arrival_confirmed_at',
        'arrival_flag', 'checkin_distance_m', 'checkin_accuracy_m',
        'checkin_latitude', 'checkin_longitude', 'checkin_at',
        'checkout_latitude', 'checkout_longitude', 'checkout_at', 'notes',
    ];

    protected $casts = [
        'purpose' => VisitPurpose::class,
        'status' => VisitStatus::class,
        'is_out_of_route' => 'boolean',
        'arrival_confirmed' => 'boolean',
        'arrival_confirmed_at' => 'datetime',
        'checkin_at' => 'datetime',
        'checkout_at' => 'datetime',
        'checkin_latitude' => 'decimal:7',
        'checkin_longitude' => 'decimal:7',
        'checkout_latitude' => 'decimal:7',
        'checkout_longitude' => 'decimal:7',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Route, $this> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /** @return BelongsTo<WorkSession, $this> */
    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }

    /** @return BelongsTo<DailyVisitAssignment, $this> */
    public function dailyVisitAssignment(): BelongsTo
    {
        return $this->belongsTo(DailyVisitAssignment::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<ReturnRecord, $this> */
    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRecord::class);
    }

    /** @return BelongsTo<VisitReport, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(VisitReport::class);
    }
}
