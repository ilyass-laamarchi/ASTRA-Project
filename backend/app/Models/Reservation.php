<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Captures an immutable server-priced rental request and its workflow state. */
class Reservation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['reservation_number', 'user_id', 'car_id', 'start_date', 'end_date', 'rental_days', 'daily_price', 'total_amount', 'status', 'client_message', 'internal_note'];

    /** Casts rental dates and money values while keeping the end date exclusive. */
    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'daily_price' => 'decimal:2', 'total_amount' => 'decimal:2'];
    }

    /** Returns the client who created this reservation. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Returns the reserved vehicle. */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /** Returns every payment attempt associated with this reservation. */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
