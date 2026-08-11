<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User2theme4Transaction extends Model
{
    protected $table = 'user2theme4_transactions';

    protected $fillable = [
        'userID',
        'theme4ID',
        'amount',
        'status',
        'current_hostID',
        'otp',
        'expires_at',
        'attempts',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public function user()
    {
        return $this->belongsTo(User::class, 'userID');
    }

    public function theme()
    {
        return $this->belongsTo(Theme4user::class, 'theme4ID');
    }
}
