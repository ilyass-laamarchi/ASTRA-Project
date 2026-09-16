<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores administrator-editable agency settings as simple key/value rows. */
class ApplicationSetting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Returns all persisted settings as a key/value array for API responses. */
    public static function values(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }
}
