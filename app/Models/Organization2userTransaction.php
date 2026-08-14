<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization2userTransaction extends Model
{
    protected $table = 'organization2user_transactions';

    protected $fillable = [
        'organizationID',
        'userID',
        'amount',
        'status',
        'otp',
        'expires_at',
        'current_hostID',
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

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organizationID');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'userID');
    }
}
