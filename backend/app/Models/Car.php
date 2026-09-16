<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Stores the operational and commercial description of an ASTRA vehicle. */
class Car extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'registration_number', 'brand', 'model', 'year', 'color', 'seats', 'doors', 'fuel_type', 'transmission', 'daily_price', 'mileage', 'description', 'operational_status', 'is_active'];

    /** Casts commercial and activation fields to predictable PHP values. */
    protected function casts(): array
    {
        return ['daily_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    /** Returns the fleet category assigned to this car. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Returns car images in their staff-defined display order. */
    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)->orderBy('sort_order');
    }

    /** Returns the image marked as primary when one exists. */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(CarImage::class)->where('is_primary', true);
    }

    /** Returns every rental request made for this car. */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
