<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordChangeRequest extends Model
{
    protected $table = 'password_change_requests';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used',
    ];
}
