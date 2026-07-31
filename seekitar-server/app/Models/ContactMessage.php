<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'category', 'message', 'status'];

    // No casts needed for status since it's a simple string.
    // Default status 'new' is handled in migration.
}
