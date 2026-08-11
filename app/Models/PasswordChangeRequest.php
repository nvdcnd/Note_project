<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasswordChangeRequest extends Model
{
    use SoftDeletes;

    protected $table = 'password_change_requests';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
        'attempts' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('used', false)->where('expires_at', '>', now());
    }
}
