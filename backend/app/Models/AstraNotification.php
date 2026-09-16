<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Stores a private in-application notification for one ASTRA user. */
class AstraNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'message', 'data', 'read_at'];

    /** Casts JSON payloads and read timestamps to convenient PHP values. */
    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime'];
    }

    /** Returns the notification recipient. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
