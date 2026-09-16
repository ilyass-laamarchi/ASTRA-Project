<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stores payment lifecycle metadata; raw card details never enter ASTRA. */
class Payment extends Model
{
    protected $fillable = ['reservation_id', 'user_id', 'payment_reference', 'provider', 'provider_session_id', 'provider_payment_id', 'amount', 'currency', 'status', 'payment_method', 'failure_reason', 'metadata', 'paid_at', 'refunded_at'];

    protected $hidden = ['metadata'];

    /** Casts provider metadata, money, and lifecycle timestamps. */
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'metadata' => 'array', 'paid_at' => 'datetime', 'refunded_at' => 'datetime'];
    }

    /** Returns the reservation this payment settles. */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /** Returns the client who owns this payment. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
