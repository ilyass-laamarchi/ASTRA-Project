<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Groups vehicles into client-friendly fleet segments. */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    /** Casts the public visibility flag to a PHP boolean. */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Returns all vehicles assigned to this category. */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }
}
