<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme4orgTransaction extends Model
{
    protected $table = 'theme4org_transactions';

    protected $fillable = [
        'organizationID',
        'themeID',
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

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organizationID');
    }

    public function theme()
    {
        return $this->belongsTo(Theme4org::class, 'themeID');
    }
}
