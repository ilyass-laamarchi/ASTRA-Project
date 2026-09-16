<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** References a locally stored vehicle image without embedding binary data. */
class CarImage extends Model
{
    protected $fillable = ['car_id', 'path', 'alt_text', 'is_primary', 'sort_order'];

    /** Casts the primary flag to a PHP boolean. */
    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    /** Returns the car that owns this image. */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }
}
