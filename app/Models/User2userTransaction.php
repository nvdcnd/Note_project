<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User2userTransaction extends Model
{
    protected $table = 'user2user_transactions';

    protected $fillable = [
        'from',
        'to',
        'amount',
        'status',
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

    public function sender()
    {
        return $this->belongsTo(User::class, 'from');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'to');
    }

    public function scopeWhereParticipant($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('from', $userId)->orWhere('to', $userId);
        });
    }
}
