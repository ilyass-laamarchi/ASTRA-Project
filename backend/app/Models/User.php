<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/** Represents a client, Responsable ASTRA, or administrator. */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_CLIENT = 'client';

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'avatar_path', 'notification_preferences', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['full_name'];

    /** Casts passwords securely and exposes JSON/boolean profile fields. */
    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'notification_preferences' => 'array'];
    }

    /** Builds a display name from the stored first and last names. */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** Returns reservations created by this client. */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Returns payments owned by this client. */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Returns private ASTRA notifications sent to this user. */
    public function astraNotifications(): HasMany
    {
        return $this->hasMany(AstraNotification::class);
    }

    /** Returns whether the account is a Responsable or administrator. */
    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN], true);
    }
}
