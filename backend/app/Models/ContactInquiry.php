<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores a public contact form submission for later staff follow-up. */
class ContactInquiry extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'message', 'status'];
}
