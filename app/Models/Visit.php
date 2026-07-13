<?php

namespace App\Models;

use App\Enums\VisitPurpose;
use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    protected $fillable = [
        'user_id', 'customer_id', 'route_id', 'work_session_id',
        'daily_visit_assignment_id',
        'purpose', 'status', 'is_out_of_route',
        'arrival_confirmed', 'arrival_confirmed_at',
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

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function route(): BelongsTo { return $this->belongsTo(Route::class); }
    public function workSession(): BelongsTo { return $this->belongsTo(WorkSession::class); }
    public function dailyVisitAssignment(): BelongsTo { return $this->belongsTo(DailyVisitAssignment::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function returns(): HasMany { return $this->hasMany(ReturnRecord::class); }
    public function report(): BelongsTo { return $this->belongsTo(VisitReport::class); }
}