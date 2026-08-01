<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Password_change_request extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used',
    ];
}
